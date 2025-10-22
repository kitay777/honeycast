<script setup>
import { Head, Link, useForm, router, usePage } from "@inertiajs/vue3";
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";

/** 下段パネル参照 */
const lowerPaneRef = ref(null);

/** route() のフォールバック */
const urlFor = (name, params = {}, fallback = "") => {
  try {
    if (typeof route === "function") {
      const u = route(name, params);
      if (typeof u === "string" && u.length) return u;
    }
  } catch {}
  return fallback;
};

const props = defineProps({
  users: Object,
  filters: Object,
  me: Object,
});

const page = usePage();
/** Inertia の flash は page.props.flash */
const flash = computed(() => page.props?.flash ?? {});

/* 一覧・検索 */
const usersData = computed(() => props.users?.data ?? []);
const usersLinks = computed(() => props.users?.links ?? []);
const q = ref(props.filters?.q ?? "");
function search() {
  router.get("/admin/users", { q: q.value }, { preserveState: true, replace: true });
}

/* ===== スプリット（上下：Pointer Events 版・スクロール殺さない完全版） ===== */
const SPLIT_KEY = "admin_users_split";
function clampPct(v) {
  v = Number.isFinite(v) ? v : 55;
  return Math.min(Math.max(v, 25), 80); // 25%〜80%
}
const topPct = ref(clampPct(parseInt(localStorage.getItem(SPLIT_KEY) || "55", 10)));

const dragging = ref(false);
let rafId = null;
let lastClientY = 0;

function setBodyDragCursor(on) {
  document.body.classList.toggle("no-select", on); // 選択だけ抑止（スクロールは生かす）
  document.body.style.cursor = on ? "row-resize" : "auto";
}

function startDrag(e) {
  const bar = document.getElementById("splitter");
  if (!bar) return;
  dragging.value = true;
  lastClientY = e.clientY;
  bar.setPointerCapture?.(e.pointerId);
  setBodyDragCursor(true);
}
function onPointerMove(e) {
  if (!dragging.value) return;
  lastClientY = e.clientY;
  if (rafId) return;
  rafId = requestAnimationFrame(() => {
    rafId = null;
    const pane = document.getElementById("right-pane");
    if (!pane) return;
    const r = pane.getBoundingClientRect();
    const y = Math.min(Math.max(lastClientY - r.top, 120), r.height - 160);
    topPct.value = clampPct(Math.round((y / r.height) * 100));
  });
}
function endDrag(e) {
  if (!dragging.value) return;
  dragging.value = false;
  if (rafId) { cancelAnimationFrame(rafId); rafId = null; }
  setBodyDragCursor(false);
  const bar = document.getElementById("splitter");
  if (bar && e?.pointerId != null) bar.releasePointerCapture?.(e.pointerId);
  localStorage.setItem(SPLIT_KEY, String(topPct.value));
}
// 画面外やフォーカス喪失でも確実に解除
function hardCancel() { endDrag(); }

onMounted(() => {
  window.addEventListener("pointerup", endDrag, { passive: true });
  window.addEventListener("pointercancel", endDrag, { passive: true });
  window.addEventListener("blur", hardCancel, { passive: true });
});
onBeforeUnmount(() => {
  window.removeEventListener("pointerup", endDrag);
  window.removeEventListener("pointercancel", endDrag);
  window.removeEventListener("blur", hardCancel);
  setBodyDragCursor(false);
});

/* ===== 下段内スクロールの共通関数 ===== */
function scrollToInLowerPane(targetId, offset = 12) {
  const pane = lowerPaneRef.value;
  const box  = document.getElementById(targetId);
  if (!pane || !box) return false;
  const top = box.offsetTop - offset;
  pane.scrollTo({ top, behavior: "smooth" });
  return true;
}

/* 編集フォーム */
const selectedId = ref(null);
const form = useForm({ id: null, name: "", email: "", phone: "", area: "", is_admin: false });
const title = computed(() => (form.id ? "ユーザー編集" : "新規ユーザー"));

function resetForm() {
  form.reset();
  form.clearErrors();
  form.id = null;
  selectedId.value = null;
  resetPointsPanel(); // ポイントパネル初期化
}
function selectForEdit(u) {
  selectedId.value = u.id;
  form.id = u.id;
  form.name = u.name || "";
  form.email = u.email || "";
  form.phone = u.phone || "";
  form.area = u.area || "";
  form.is_admin = !!u.is_admin;
  loadPoints(u.id); // ポイント読み込み

  // 値反映後に下段フォームへスクロール＆先頭入力へフォーカス
  nextTick(() => {
    const ok = scrollToInLowerPane("edit-form-box");
    if (!ok) {
      document.getElementById("edit-form-box")?.scrollIntoView({ behavior: "smooth", block: "start" });
    }
    document.querySelector("#edit-form-box input")?.focus();
  });
}
function submitCreate() {
  form.post("/admin/users", { onSuccess: () => resetForm() });
}
function submitUpdate() {
  form.put(`/admin/users/${form.id}`);
}
function remove(u) {
  if (u.id === props.me?.id) { alert("自分自身は削除できません"); return; }
  if (!confirm("削除しますか？")) return;
  router.delete(`/admin/users/${u.id}`, {
    onSuccess: () => { if (selectedId.value === u.id) resetForm(); }
  });
}

/* ───────── LINE 送信（個別） ───────── */
const lineForm = useForm({ text: "", notification_disabled: false });
const sendingLine = ref(false);
async function sendLine() {
  if (!form.id || !lineForm.text) return;
  sendingLine.value = true;
  const url = urlFor("admin.users.line.push", { user: form.id }, `/admin/users/${form.id}/line/push`);
  await lineForm.post(url, {
    preserveScroll: true,
    onFinish: () => { sendingLine.value = false; },
    onSuccess: () => { lineForm.reset("text"); },
  });
}
function openLineAndScroll(u) {
  selectForEdit(u);
  setTimeout(() => {
    if (!scrollToInLowerPane("line-send-box")) {
      document.getElementById("line-send-box")?.scrollIntoView({ behavior: "smooth", block: "start" });
    }
  }, 0);
}

/* ───────── ポイント：履歴＋調整 ───────── */
const loadingPoints = ref(false);
const points = ref({ balance: 0, history: [] });
const pointsForm = useForm({ delta: 0, reason: "" });
const pointsError = ref("");

function resetPointsPanel() {
  points.value = { balance: 0, history: [] };
  pointsForm.reset("delta", "reason");
  loadingPoints.value = false;
  pointsError.value = "";
}

async function loadPoints(userId) {
  resetPointsPanel();
  if (!userId) return;
  loadingPoints.value = true;
  try {
    const res = await fetch(`/admin/users/${userId}/points`, { headers: { Accept: "application/json" } });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    points.value = await res.json();
  } catch (e) {
    pointsError.value = "履歴の読み込みに失敗しました。";
    console.error(e);
  } finally {
    loadingPoints.value = false;
  }
}

async function submitPoints() {
  if (!form.id) return;
  pointsError.value = "";
  await pointsForm.post(`/admin/users/${form.id}/points/adjust`, {
    preserveScroll: true,
    onSuccess: () => {
      pointsForm.reset("delta", "reason");
      loadPoints(form.id);
    },
    onError: (errs) => {
      pointsError.value = errs?.delta || errs?.reason || "調整に失敗しました。";
    },
  });
}
</script>

<template>
  <Head title="ユーザー管理" />

  <AdminLayout active-key="users">
    <!-- ヘッダ -->
    <template #header>
      <div class="px-5 py-3 bg-white border-b flex items-center justify-between">
        <div class="text-xl font-semibold">ユーザー管理</div>
        <div class="flex gap-2">
          <input
            v-model="q"
            @keyup.enter="search"
            type="text"
            class="border rounded px-3 py-2 w-72"
            placeholder="名前 / メール / エリア / 電話 を検索"
          />
          <button @click="search" class="px-4 py-2 rounded bg-black text-white">検索</button>
          <button @click="resetForm" class="px-3 py-2 rounded bg-gray-100">＋ 新規</button>
        </div>
      </div>
      <div v-if="flash?.success" class="px-5 py-2 bg-emerald-50 text-emerald-700 border-b">{{ flash.success }}</div>
      <div v-if="flash?.error" class="px-5 py-2 bg-red-50 text-red-700 border-b whitespace-pre-line">{{ flash.error }}</div>
    </template>

    <!-- 上：一覧 -->
    <div id="right-pane" class="p-4 overflow-auto" :style="{ height: `calc(${topPct}% - 56px)` }">
      <div class="bg-white rounded-2xl shadow divide-y">
        <div v-if="usersData.length === 0" class="px-4 py-6 text-sm text-gray-500">
          ユーザーがいません（または読み込み中）
        </div>

        <div
          v-for="u in usersData"
          :key="u.id"
          class="px-4 py-3 flex items-center justify-between hover:bg-gray-50"
          :class="selectedId === u.id ? 'bg-gray-50' : ''"
        >
          <div>
            <div class="font-medium">
              {{ u.name }} <span class="text-xs text-gray-500 ml-2">#{{ u.id }}</span>
              <span v-if="u.is_admin" class="ml-2 text-xs bg-black text-white px-2 py-0.5 rounded">admin</span>
            </div>
            <div class="text-xs text-gray-500">
              {{ u.email }} ・ {{ u.area || "-" }} ・ {{ u.phone || "-" }}
              <span v-if="u.cast_profile" class="ml-2">/ Cast: {{ u.cast_profile.nickname || ("ID:" + u.cast_profile.id) }}</span>
              <span v-if="u.line_user_id" class="ml-2 text-emerald-700">/ LINE連携: 済</span>
              <span v-else class="ml-2 text-gray-400">/ LINE連携: 未</span>
            </div>
            <div class="text-[11px] text-gray-500 mt-1" v-if="selectedId !== u.id">
              残高の確認・調整は「編集」から行えます
            </div>
          </div>
          <div class="flex items-center gap-2">
            <Link :href="`/admin/casts?q=${encodeURIComponent(u.email)}`" class="text-xs px-2 py-1 rounded border">
              キャストを見る
            </Link>
            <button @click="selectForEdit(u)" class="text-sm px-2 py-1 rounded bg-blue-600 text-white">編集</button>
            <button @click="openLineAndScroll(u)" class="text-sm px-2 py-1 rounded bg-emerald-600 text-white">LINE送信</button>
            <button @click="remove(u)" class="text-sm px-2 py-1 rounded bg-red-600 text-white">削除</button>
          </div>
        </div>
      </div>

      <!-- ページネーション -->
      <div class="mt-4 flex gap-2 flex-wrap">
        <Link
          v-for="(lnk, i) in usersLinks"
          :key="i"
          :href="lnk.url || '#'"
          class="px-3 py-1 border rounded"
          :class="[ lnk.active ? 'bg-black text-white' : '', !lnk.url ? 'opacity-50 pointer-events-none' : '' ]"
          v-html="lnk.label"
        />
      </div>
    </div>

    <!-- 仕切り（Pointer Events で確実に終わる） -->
    <div
      id="splitter"
      class="h-2 bg-gray-200 hover:bg-gray-300 cursor-row-resize"
      @pointerdown="startDrag"
      @pointermove="onPointerMove"
      @pointerup="endDrag"
    ></div>

    <!-- 下：フォーム＆各パネル（下段パネル内スクロール対象） -->
    <div class="p-4 overflow-auto" :style="{ height: `calc(${100 - topPct}% - 2px)` }" ref="lowerPaneRef">
      <div class="bg-white rounded-2xl shadow p-4" id="edit-form-box">
        <h2 class="text-lg font-semibold mb-3">{{ title }}</h2>

        <form @submit.prevent="form.id ? submitUpdate() : submitCreate()" class="grid grid-cols-12 gap-3">
          <!-- 基本情報 -->
          <div class="col-span-12 md:col-span-4">
            <label class="text-sm">名前</label>
            <input v-model="form.name" type="text" class="w-full border rounded px-3 py-2" />
            <div v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</div>
          </div>
          <div class="col-span-12 md:col-span-4">
            <label class="text-sm">メール</label>
            <input v-model="form.email" type="email" class="w-full border rounded px-3 py-2" />
            <div v-if="form.errors.email" class="text-xs text-red-600 mt-1">{{ form.errors.email }}</div>
          </div>
          <div class="col-span-6 md:col-span-2">
            <label class="text-sm">電話</label>
            <input v-model="form.phone" type="text" class="w-full border rounded px-3 py-2" />
          </div>
          <div class="col-span-6 md:col-span-2">
            <label class="text-sm">エリア</label>
            <input v-model="form.area" type="text" class="w-full border rounded px-3 py-2" />
          </div>
          <div class="col-span-12 md:col-span-3 flex items-center gap-2">
            <input id="admin" type="checkbox" v-model="form.is_admin" class="h-4 w-4" />
            <label for="admin" class="text-sm">管理者</label>
          </div>

          <div class="col-span-12 flex gap-2 pt-2">
            <button
              type="submit"
              class="px-4 py-2 rounded bg-black text-white disabled:opacity-50"
              :disabled="form.processing"
            >
              {{ form.id ? "更新する" : "作成する" }}
            </button>
            <button type="button" @click="resetForm" class="px-4 py-2 rounded bg-gray-100">クリア</button>
          </div>
        </form>
      </div>

      <!-- ───────── ポイント（履歴＋調整） ───────── -->
      <div class="bg-white rounded-2xl shadow p-4 mt-4">
        <h3 class="text-lg font-semibold mb-2">ポイント</h3>

        <div class="flex flex-wrap items-end gap-4">
          <div class="text-sm">
            残高：
            <span class="text-2xl font-bold">
              {{ loadingPoints ? "…" : (points.balance || 0).toLocaleString() }}
            </span> pt
          </div>

          <form @submit.prevent="submitPoints" class="flex items-end gap-2">
            <div>
              <label class="text-sm">増減（例 +100 / -50）</label>
              <input v-model.number="pointsForm.delta" type="number" class="border rounded px-3 py-2 w-28" />
            </div>
            <div>
              <label class="text-sm">理由（任意）</label>
              <input v-model="pointsForm.reason" type="text" class="border rounded px-3 py-2 w-64" />
            </div>
            <button
              :disabled="pointsForm.processing || !form.id"
              class="px-3 py-2 rounded bg-emerald-600 text-white disabled:opacity-60"
            >
              反映
            </button>
          </form>
        </div>

        <div class="mt-2 text-xs text-red-600" v-if="pointsError">{{ pointsError }}</div>

        <div class="mt-3">
          <div class="text-sm font-semibold mb-1">履歴（最新20件）</div>
          <div v-if="loadingPoints" class="text-gray-500 text-sm">読み込み中…</div>
          <div v-else-if="!points.history.length" class="text-gray-500 text-sm">履歴はまだありません。</div>
          <ul v-else class="divide-y">
            <li v-for="h in points.history" :key="h.id" class="py-2 flex items-center justify-between text-sm">
              <div :class="h.delta >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                {{ h.delta >= 0 ? '+' : '' }}{{ h.delta }} pt
                <span class="text-gray-500 ml-2">{{ h.reason || '—' }}</span>
              </div>
              <div class="text-right text-gray-500">
                <div>{{ h.created_at }}</div>
                <div class="text-xs">残高: {{ h.after }}</div>
              </div>
            </li>
          </ul>
        </div>
      </div>

      <!-- ───────── LINE送信ボックス ───────── -->
      <div id="line-send-box" class="bg-white rounded-2xl shadow p-4 mt-4">
        <h3 class="text-lg font-semibold mb-2">LINE メッセージ送信</h3>
        <div class="text-sm text-gray-600 mb-2">
          送信先: <span class="font-medium">ユーザー #{{ form.id || "-" }}</span>
          <span class="ml-2 text-gray-500">(選択してから送信してください)</span>
        </div>
        <div class="space-y-2">
          <label class="block text-sm">メッセージ</label>
          <textarea
            v-model="lineForm.text"
            rows="4"
            class="w-full border rounded px-3 py-2"
            placeholder="送信内容（最大1000文字）"
          ></textarea>

          <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" v-model="lineForm.notification_disabled" />
            通知を鳴らさない
          </label>

          <div class="mt-1 flex items-center gap-2">
            <button
              type="button"
              @click="sendLine"
              :disabled="sendingLine || !form.id || !lineForm.text"
              class="px-4 py-2 rounded text-white"
              :class="(sendingLine || !form.id || !lineForm.text) ? 'bg-gray-400' : 'bg-emerald-600 hover:brightness-110'"
            >
              送信
            </button>
            <span class="text-xs text-gray-500">※ 未連携の場合はエラーが返ります</span>
          </div>
        </div>
      </div>
      <!-- ───────────────────────────────────── -->
    </div>
  </AdminLayout>
</template>

<style>
/* ドラッグ中もスクロールを殺さず、選択のみ抑止 */
.no-select {
  user-select: none;
  -webkit-user-select: none;
}
</style>
