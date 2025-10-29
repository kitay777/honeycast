<script setup>
import { Head, Link, useForm, router, usePage } from "@inertiajs/vue3";
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import CastPhotosManager from "@/Components/Admin/CastPhotosManager.vue";

/** route() フォールバック */
const urlFor = (name, params = {}, fallback = "") => {
    try {
        if (typeof route === "function") {
            const u = route(name, params);
            if (typeof u === "string" && u.length) return u;
        }
    } catch {}
    return fallback;
};

const props = defineProps({ casts: Object, filters: Object });
const page = usePage();

/** flash メッセージ */
const flash = computed(() => page.props?.flash ?? {});
const lowerPaneRef = ref(null);

/* 一覧・ページネーション */
const castsData = computed(() => props.casts?.data ?? []);
const castsLinks = computed(() => props.casts?.links ?? []);

/* 検索 */
const q = ref(props.filters?.q ?? "");
function search() {
    router.get(
        "/admin/casts",
        { q: q.value },
        { preserveState: true, replace: true }
    );
}

/* 上下スプリット */
const topPct = ref(
    parseInt(localStorage.getItem("admin_casts_split") || "55", 10)
);
let dragging = false;
function startDrag(e) {
    dragging = true;
    document.body.style.cursor = "row-resize";
    e.preventDefault();
}
function onDrag(e) {
    if (!dragging) return;
    const el = document.getElementById("right-pane");
    if (!el) return;
    const r = el.getBoundingClientRect();
    const y = Math.min(Math.max(e.clientY - r.top, 120), r.height - 160);
    topPct.value = Math.min(Math.max(Math.round((y / r.height) * 100), 25), 80);
}
function endDrag() {
    if (!dragging) return;
    dragging = false;
    document.body.style.cursor = "default";
    localStorage.setItem("admin_casts_split", String(topPct.value));
}
onMounted(() => {
    window.addEventListener("mousemove", onDrag);
    window.addEventListener("mouseup", endDrag);
    window.addEventListener("mouseleave", endDrag);
    window.addEventListener("blur", endDrag);
});
onBeforeUnmount(() => {
    window.removeEventListener("mousemove", onDrag);
    window.removeEventListener("mouseup", endDrag);
    window.removeEventListener("mouseleave", endDrag);
    window.removeEventListener("blur", endDrag);
});

/* 編集フォーム */
const selectedId = ref(null);
const form = useForm({
    id: null,
    name: "",
    email: "",
    nickname: "",
    rank: "",
    age: null,
    height_cm: null,
    cup: "",
    style: "",
    alcohol: "",
    mbti: "",
    area: "",
    tags: "",
    freeword: "",
    photo: null,
});
const title = computed(() => (form.id ? "キャスト編集" : "新規キャスト"));

/** ID補正 */
const normalizeId = (x) => {
    const s = String(x ?? "").normalize("NFKC");
    const m = s.match(/[0-9]+/);
    return m ? Number(m[0]) : null;
};

/** 一覧→フォーム選択 */
const castById = computed(() => {
    const m = new Map();
    for (const c of castsData.value) {
        const id = normalizeId(c.id);
        if (id != null) m.set(id, c);
    }
    return m;
});
const selectedCast = computed(
    () => castById.value.get(selectedId.value) ?? null
);

function resetForm() {
    form.reset();
    form.clearErrors();
    form.id = null;
    selectedId.value = null;
    resetPointsPanel();
}

function selectForEdit(c) {
    const nid = normalizeId(c.id);
    selectedId.value = nid;
    requestAnimationFrame(() => {
        form.id = nid;
        form.name = c.user?.name ?? "";
        form.email = c.user?.email ?? "";
        form.nickname = c.nickname ?? "";
        form.rank = c.rank ?? "";
        form.age = c.age ?? null;
        form.height_cm = c.height_cm ?? null;
        form.cup = c.cup ?? "";
        form.style = c.style ?? "";
        form.alcohol = c.alcohol ?? "";
        form.mbti = c.mbti ?? "";
        form.area = c.area ?? "";
        form.tags = c.tags ?? "";
        form.freeword = c.freeword ?? "";
        form.photo = null;
        loadPoints(c?.user?.id ?? null);
        nextTick(() => {
            const pane = lowerPaneRef.value;
            const box = document.getElementById("edit-form-box");
            if (pane && box) {
                const y = box.offsetTop - 12;
                pane.scrollTo({ top: y, behavior: "smooth" });
            } else {
                box?.scrollIntoView({ behavior: "smooth", block: "start" });
            }
            document.querySelector("#edit-form-box input")?.focus();
        });
    });
}

function submitCreate() {
    form.post("/admin/casts", {
        forceFormData: true,
        onSuccess: () => resetForm(),
    });
}

function submitUpdate() {
    form.transform((d) => {
        const out = {};
        for (const [k, v] of Object.entries(d)) {
            if (k === "id") continue;
            if (k === "photo" && !(v instanceof File)) continue;
            if (v === null || v === undefined) continue;
            if (typeof v === "string" && v.trim() === "") continue;
            out[k] = v;
        }
        out._method = "put";
        return out;
    }).post(`/admin/casts/${form.id}`, {
        forceFormData: true,
        preserveScroll: true,
    });
}

function remove(c) {
    if (!confirm("削除しますか？")) return;
    router.delete(`/admin/casts/${c.id}`, {
        onSuccess: () => {
            if (selectedId.value === normalizeId(c.id)) resetForm();
        },
    });
}

/* ───────── LINE 送信 ───────── */
const lineForm = useForm({ text: "", notification_disabled: false });
const sendingLine = ref(false);
function openLineAndScroll(c) {
    selectForEdit(c);
    setTimeout(() => {
        document
            .getElementById("line-send-box")
            ?.scrollIntoView({ behavior: "smooth", block: "start" });
    }, 0);
}
async function sendLine(castUserId) {
    if (!castUserId || !lineForm.text || sendingLine.value) return;
    sendingLine.value = true;
    const url = urlFor(
        "admin.users.line.push",
        { user: castUserId },
        `/admin/users/${castUserId}/line/push`
    );
    await lineForm.post(url, {
        preserveScroll: true,
        onFinish: () => {
            sendingLine.value = false;
        },
        onSuccess: () => {
            lineForm.reset("text");
        },
    });
}

/* ───────── ポイント管理 ───────── */
const loadingPoints = ref(false);
const points = ref({ balance: 0, history: [] });
const pointsForm = useForm({ delta: 0, reason: "" });
const pointsError = ref("");
const currentUserId = ref(null);

function resetPointsPanel() {
    points.value = { balance: 0, history: [] };
    pointsForm.reset("delta", "reason");
    pointsError.value = "";
    loadingPoints.value = false;
    currentUserId.value = null;
}
async function loadPoints(userId) {
    resetPointsPanel();
    if (!userId) return;
    currentUserId.value = userId;
    loadingPoints.value = true;
    try {
        const res = await fetch(`/admin/users/${userId}/points`, {
            headers: { Accept: "application/json" },
        });
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
    if (!currentUserId.value) return;
    pointsError.value = "";
    await pointsForm.post(`/admin/users/${currentUserId.value}/points/adjust`, {
        preserveScroll: true,
        onSuccess: () => {
            pointsForm.reset("delta", "reason");
            loadPoints(currentUserId.value);
        },
        onError: (errs) => {
            pointsError.value =
                errs?.delta || errs?.reason || "調整に失敗しました。";
        },
    });
}
</script>

<template>
    <Head title="キャスト管理" />
    <AdminLayout active-key="casts">
        <template #header>
            <div
                class="px-5 py-3 bg-white border-b flex items-center justify-between"
            >
                <div class="text-xl font-semibold">キャスト管理</div>
                <div class="flex gap-2">
                    <input
                        v-model="q"
                        @keyup.enter="search"
                        type="text"
                        class="border rounded px-3 py-2 w-72"
                        placeholder="ニックネーム / ユーザー名 / メール / エリアを検索"
                    />
                    <button
                        type="button"
                        @click="search"
                        class="px-4 py-2 rounded bg-black text-white"
                    >
                        検索
                    </button>
                    <button
                        type="button"
                        @click="resetForm"
                        class="px-3 py-2 rounded bg-gray-100"
                    >
                        ＋ 新規
                    </button>
                </div>
            </div>
            <div
                v-if="flash?.success"
                class="px-5 py-2 bg-emerald-50 text-emerald-700 border-b"
            >
                {{ flash.success }}
            </div>
            <div
                v-if="flash?.error"
                class="px-5 py-2 bg-red-50 text-red-700 border-b whitespace-pre-line"
            >
                {{ flash.error }}
            </div>
        </template>

        <!-- 上：一覧 -->
        <div
            id="right-pane"
            class="p-4 overflow-auto"
            :style="{ height: `calc(${topPct}% - 56px)` }"
        >
            <div class="bg-white rounded-2xl shadow divide-y">
                <div
                    v-if="castsData.length === 0"
                    class="px-4 py-6 text-sm text-gray-500"
                >
                    キャストがいません（または読み込み中）
                </div>
                <div
                    v-for="c in castsData"
                    :key="`cast-${normalizeId(c.id) ?? c.id}`"
                    class="px-4 py-3 flex items-center justify-between hover:bg-gray-50"
                    :class="selectedCast?.id === c.id ? 'bg-gray-50' : ''"
                >
                    <div class="flex items-center gap-3">
                        <img
                            v-if="c.photo_path"
                            :src="`/storage/${c.photo_path}`"
                            class="w-12 h-12 object-cover rounded-xl"
                        />
                        <div>
                            <div class="font-medium">
                                {{ c.nickname || c.user?.name || "(無名)" }}
                                <span class="text-xs text-gray-500 ml-2"
                                    >#{{ c.id }}</span
                                >
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ c.user?.email || "-" }} ・
                                {{ c.area || "-" }} ・
                                {{ c.rank || "-" }}
                                <span
                                    v-if="c.user?.line_user_id"
                                    class="ml-2 text-emerald-700"
                                    >/ LINE連携: 済</span
                                >
                                <span v-else class="ml-2 text-gray-400"
                                    >/ LINE連携: 未</span
                                >
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Link
                            :href="`/admin/users?q=${encodeURIComponent(
                                c.user?.email || ''
                            )}`"
                            class="text-xs px-2 py-1 rounded border"
                            >ユーザーを見る</Link
                        >
                        <button
                            type="button"
                            @click="selectForEdit(c)"
                            class="text-sm px-2 py-1 rounded bg-blue-600 text-white"
                        >
                            編集
                        </button>
                        <button
                            type="button"
                            @click="openLineAndScroll(c)"
                            class="text-sm px-2 py-1 rounded bg-emerald-600 text-white"
                        >
                            LINE送信
                        </button>
                        <button
                            type="button"
                            @click="remove(c)"
                            class="text-sm px-2 py-1 rounded bg-red-600 text-white"
                        >
                            削除
                        </button>
                    </div>
                </div>
            </div>

            <!-- ページネーション -->
            <div class="mt-4 flex gap-2 flex-wrap">
                <Link
                    v-for="(lnk, i) in castsLinks"
                    :key="i"
                    :href="lnk.url || '#'"
                    class="px-3 py-1 border rounded"
                    :class="[
                        lnk.active ? 'bg-black text-white' : '',
                        !lnk.url ? 'opacity-50 pointer-events-none' : '',
                    ]"
                    v-html="lnk.label"
                />
            </div>
        </div>

        <!-- 仕切り -->
        <div
            class="h-2 bg-gray-200 hover:bg-gray-300 cursor-row-resize"
            @mousedown="startDrag"
        ></div>

        <!-- 下段 -->
        <div
            class="p-4 overflow-auto"
            :style="{ height: `calc(${100 - topPct}% - 2px)` }"
            ref="lowerPaneRef"
        >
            <div class="bg-white rounded-2xl shadow p-4" id="edit-form-box">
                <h2 class="text-lg font-semibold mb-3">{{ title }}</h2>

                <!-- キャストフォーム -->
                <form
                    @submit.prevent="form.id ? submitUpdate() : submitCreate()"
                    class="grid grid-cols-12 gap-3"
                >
                    <!-- User -->
                    <div class="col-span-12 md:col-span-4">
                        <label class="text-sm">ユーザー名</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full border rounded px-3 py-2"
                        />
                    </div>
                    <div class="col-span-12 md:col-span-4">
                        <label class="text-sm">メール</label>
                        <input
                            v-model="form.email"
                            type="email"
                            class="w-full border rounded px-3 py-2"
                        />
                    </div>

                    <!-- Profile fields -->
                    <div
                        v-for="f in [
                            ['nickname', 'ニックネーム'],
                            ['rank', 'ランク'],
                            ['age', '年齢'],
                            ['height_cm', '身長(cm)'],
                            ['cup', 'Cup'],
                            ['style', 'Style'],
                            ['alcohol', 'お酒'],
                            ['mbti', 'MBTI'],
                            ['area', 'エリア'],
                        ]"
                        :key="f[0]"
                        class="col-span-6 md:col-span-2"
                    >
                        <label class="text-sm">{{ f[1] }}</label>
                        <input
                            v-model="form[f[0]]"
                            type="text"
                            class="w-full border rounded px-3 py-2"
                        />
                    </div>

                    <div class="col-span-12">
                        <label class="text-sm">タグ（カンマ区切り）</label>
                        <input
                            v-model="form.tags"
                            type="text"
                            class="w-full border rounded px-3 py-2"
                        />
                    </div>
                    <div class="col-span-12">
                        <label class="text-sm">フリーワード</label>
                        <textarea
                            v-model="form.freeword"
                            rows="3"
                            class="w-full border rounded px-3 py-2"
                        ></textarea>
                    </div>

                    <div class="col-span-12 flex gap-2 pt-2">
                        <button
                            type="submit"
                            class="px-4 py-2 rounded bg-black text-white"
                        >
                            更新する
                        </button>
                        <button
                            type="button"
                            @click="resetForm"
                            class="px-4 py-2 rounded bg-gray-100"
                        >
                            クリア
                        </button>
                    </div>
                </form>

                <!-- ✅ formの外！ -->
                <div
                    v-if="form.id"
                    class="mt-6 bg-white rounded-2xl shadow p-4"
                >
                    <h3 class="text-lg font-semibold mb-2">
                        写真（メイン／サブ）
                    </h3>
                    <CastPhotosManager :cast-id="form.id" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
