<!-- resources/js/Pages/Cast/ProfileEdit.vue -->
<script setup>
console.log("✅ VITE_LIFF_ID:", import.meta.env.VITE_LIFF_ID);
import AppLayout from "@/Layouts/AppLayout.vue";
import { Head, Link, useForm, router, usePage } from "@inertiajs/vue3";
import { ref, computed, watch, onMounted } from "vue";
import axios from "axios";

/* =========================================================
   ✅ LIFF 自動連携（ログイン時に自動登録）
   ========================================================= */
const autoLinkLine = async () => {
    if (line.value.linked) return;
    try {
        if (!LIFF_ID) return;

        // LIFF SDKをロード
        await loadScript("https://static.line-scdn.net/liff/edge/2/sdk.js");
        await window.liff.init({ liffId: LIFF_ID });

        // LINEログインしていなければリダイレクト
        if (!window.liff.isLoggedIn()) {
            window.liff.login();
            return;
        }

        // ユーザープロフィール取得
        const prof = await window.liff.getProfile();
        console.log("✅ LINE profile:", prof);

        // 友だち状態確認（権限がない場合はスルー）
        let friendFlag = true;
        try {
            const fr = await window.liff.getFriendship();
            friendFlag = !!fr.friendFlag;
        } catch (e) {
            console.warn("⚠️ getFriendship未対応または権限なし:", e);
        }

        if (!friendFlag) {
            console.log("❌ まだ友だち登録されていません");
            return;
        }

        // サーバへ登録（CSRF対応）
        const token = document.querySelector(
            'meta[name="csrf-token"]'
        )?.content;
        const res = await axios.post(
            "/line/link/direct",
            {
                uid: prof.userId,
                displayName: prof.displayName,
            },
            { headers: { "X-CSRF-TOKEN": token } }
        );

        if (res.data?.ok) {
            line.value.linked = true;
            line.value.userId = prof.userId;
            line.value.displayName = prof.displayName;
            console.log("✅ LINE自動連携成功");
            // auth / cast 再取得（ヘッダーなど即反映）
            router.reload({ only: ["auth", "cast"] });
        } else {
            console.warn("⚠️ LINE自動連携サーバー応答エラー:", res.data);
        }
    } catch (e) {
        console.error("❌ LINE自動連携エラー:", e);
    }
};

/* =========================================================
   🔁 LINE連携状態ポーリング
   ========================================================= */
const polling = ref(false);
let pollTimer = null;
const pollStatusOnce = async () => {
    try {
        const res = await fetch(
            urlFor("line.link.status", {}, "/line/link/status") + "?json=1",
            { credentials: "include", headers: { Accept: "application/json" } }
        );
        if (!res.ok) return;
        const j = await res.json();
        if (j?.linked) {
            line.value.linked = true;
            line.value.userId = j.user_id ?? null;
            line.value.displayName = j.display_name ?? null;
            router.reload({ only: ["auth"] });
            stopPolling();
        }
    } catch (_) {}
};
const startPolling = (intervalMs = 4000, timeoutMs = 2 * 60 * 1000) => {
    if (polling.value) return;
    polling.value = true;
    pollStatusOnce();
    pollTimer = window.setInterval(pollStatusOnce, intervalMs);
    window.setTimeout(() => stopPolling(), timeoutMs);
};
const stopPolling = () => {
    polling.value = false;
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
};

/* =========================================================
   📄 ページ情報・共通関数
   ========================================================= */
const page = usePage();
const LIFF_ID = (
    page.props?.value?.liff?.id ??
    import.meta.env?.VITE_LIFF_ID ??
    ""
).toString();

// route() が未定義でも動作させるフォールバック
const urlFor = (name, params = {}, fallback = "") => {
    try {
        if (typeof route === "function") {
            const u = route(name, params);
            if (typeof u === "string" && u.length) return u;
        }
    } catch {}
    return fallback;
};

// 動的スクリプトローダー
const loadScript = (src) =>
    new Promise((resolve, reject) => {
        const s = document.createElement("script");
        s.src = src;
        s.async = true;
        s.onload = resolve;
        s.onerror = reject;
        document.head.appendChild(s);
    });

/* =========================================================
   🧱 Props / 基本フォーム
   ========================================================= */
const props = defineProps({
    cast: { type: Object, default: null },
    pendingPermissions: { type: Array, default: () => [] },
    available_tags: { type: Array, default: () => [] },
    pendingPhotoPermissions: { type: Array, default: () => [] },
});

const p = computed(() => props.cast ?? {});

const form = useForm({
    id: p.value?.id ?? null,
    nickname: p.value?.nickname ?? "",
    rank: p.value?.rank ?? "",
    age: p.value?.age ?? "",
    height_cm: p.value?.height_cm ?? "",
    cup: p.value?.cup ?? "",
    style: p.value?.style ?? "",
    alcohol: p.value?.alcohol ?? "",
    mbti: p.value?.mbti ?? "",
    area: p.value?.area ?? "",
    tag_ids: Array.isArray(props.cast?.tag_ids) ? [...props.cast.tag_ids] : [],
    freeword: p.value?.freeword ?? "",
    photo: null,
});

/* =========================================================
   📸 画像管理（既存ロジック維持）
   ========================================================= */
const existing = ref([]);
const primaryId = ref(null);
const newFiles = ref([]);

watch(
    () => props.cast?.photos,
    (photos) => {
        const arr = (photos ?? []).map((ph) => ({
            id: ph.id,
            url: ph.url ?? (ph.path ? `/storage/${ph.path}` : null),
            sort_order: ph.sort_order ?? 0,
            is_primary: !!ph.is_primary,
            _blur: ph.should_blur === true,
            _delete: false,
        }));
        existing.value = arr;
        primaryId.value = arr.find((x) => x.is_primary)?.id || null;
    },
    { immediate: true }
);

/* =========================================================
   ✳️ LINE連携状態・UI制御
   ========================================================= */
const line = ref({
    linked: !!props.cast?.line_user_id,
    displayName: props.cast?.line_display_name ?? null,
    userId: props.cast?.line_user_id ?? null,
});
const lineLinking = ref(false);
const lineCode = ref(null);
const lineBotUrl = ref(null);
const lineBotQr = ref(null);

const envBotUrl = computed(() => page.props?.value?.line_env?.bot_url ?? null);
const envBotQr = computed(() => page.props?.value?.line_env?.bot_qr ?? null);
const viteBotUrl = import.meta.env.VITE_LINE_BOT_ADD_URL || null;
const viteBotQr = import.meta.env.VITE_LINE_BOT_QR || null;

const addUrl = computed(
    () => lineBotUrl.value || envBotUrl.value || viteBotUrl
);
const addQr = computed(() => lineBotQr.value || envBotQr.value || viteBotQr);

/* =========================================================
   🚀 マウント時自動連携
   ========================================================= */
onMounted(async () => {
    // LIFF が読み込まれていなければ、ロード完了を待ってから実行
    if (!line.value.linked) {
        if (typeof window.liff === "undefined") {
            await loadScript("https://static.line-scdn.net/liff/edge/2/sdk.js");
        }

        // ✅ すでにログイン済みなら再ログインさせない
        try {
            await window.liff.init({ liffId: LIFF_ID });
            if (!window.liff.isLoggedIn()) {
                window.liff.login();
                return;
            }
        } catch (e) {
            console.warn("LIFF初期化エラー（まだロード中かも）", e);
            return;
        }

        // ここで初めて連携処理を呼ぶ
        autoLinkLine();
    }
});
</script>

<template>
    <AppLayout>
        <Head title="キャストプロフィール編集" />
        <div class="min-h-dvh w-screen bg-black flex justify-center md:py-6">
            <div
                class="relative w-full max-w-[390px] max-h-dvh mx-auto bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%] overflow-y-auto min-h-0"
            >
                <div class="px-6 py-8 text-white/90">
                    <h1 class="text-2xl font-semibold mb-6">
                        プロフィール編集
                    </h1>

                    <!-- ================= LINE 連携 ================= -->
                    <div
                        class="mb-6 p-4 rounded border border-white/20 bg-white/5"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="font-semibold">LINEで通知を受け取る</h3>
                            <span
                                v-if="line.linked"
                                class="text-xs px-2 py-1 rounded bg-green-600 text-white"
                            >
                                連携済み
                            </span>
                        </div>

                        <!-- 連携済み -->
                        <div v-if="line.linked" class="space-y-2">
                            <div class="text-sm opacity-90">
                                {{
                                    line.displayName
                                        ? `LINE: ${line.displayName}`
                                        : "LINEアカウント連携済み"
                                }}
                            </div>
                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    @click="sendLineTest"
                                    class="px-3 py-1 rounded bg-yellow-200 text-black text-sm"
                                >
                                    テスト通知を送る
                                </button>
                                <button
                                    type="button"
                                    @click="disconnectLine"
                                    class="px-3 py-1 rounded bg-gray-600 text-white text-sm"
                                >
                                    連携解除
                                </button>
                            </div>
                            <p class="text-xs opacity-70">
                                ※
                                ブロックされている場合は送信できません。解除後に再連携が必要です。
                            </p>
                        </div>

                        <!-- 未連携 -->
                        <div v-else class="space-y-3">
                            <ol
                                class="list-decimal list-inside space-y-2 text-sm opacity-90"
                            >
                                <li>
                                    下のボタンから
                                    <span class="font-semibold"
                                        >公式アカウントを友だち追加</span
                                    >
                                    してください。
                                </li>
                                <li>
                                    ログイン時に自動的にLINE連携が完了します（コード送信不要）。
                                </li>
                            </ol>

                            <div class="flex flex-wrap items-center gap-2">
                                <a
                                    v-if="addUrl"
                                    :href="addUrl"
                                    target="_blank"
                                    rel="noopener"
                                    class="px-3 py-1 rounded bg-[#06C755] text-white text-sm"
                                >
                                    友だち追加（LINEを開く）
                                </a>
                            </div>

                            <div v-if="addQr" class="pt-2">
                                <img
                                    :src="addQr"
                                    class="w-32 h-32 object-contain border border-white/10 rounded"
                                    alt="LINE QR"
                                />
                                <div class="text-xs opacity-70 mt-1">
                                    QRを読み取って友だち追加も可能です。
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- =========================================== -->

                    <!-- ======= 承認待ち（ぼかし解除・写真） ======= -->
                    <div
                        v-if="(pendingPermissions?.length || 0) > 0"
                        class="mb-6"
                    >
                        <h3 class="font-bold mt-2 mb-2">未処理の閲覧申請</h3>
                        <ul class="space-y-2">
                            <li
                                v-for="pmt in pendingPermissions"
                                :key="pmt.id"
                                class="p-3 rounded border border-white/20 bg-white/5"
                            >
                                <div class="text-sm opacity-80">
                                    申請者: {{ pmt.viewer.name }} (ID:
                                    {{ pmt.viewer.id }})
                                </div>
                                <div class="text-sm">
                                    メッセージ: {{ pmt.message || "（なし）" }}
                                </div>
                                <div class="mt-2 space-x-2">
                                    <button
                                        type="button"
                                        @click="approve(pmt.id)"
                                        class="bg-green-600 text-white rounded px-3 py-1"
                                    >
                                        承認
                                    </button>
                                    <button
                                        type="button"
                                        @click="deny(pmt.id)"
                                        class="bg-gray-500 text-white rounded px-3 py-1"
                                    >
                                        否認
                                    </button>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <div
                        v-if="(pendingPhotoPermissions?.length || 0) > 0"
                        class="mb-6"
                    >
                        <h3 class="font-bold mt-2 mb-2">
                            未処理の閲覧申請（写真）
                        </h3>
                        <ul class="grid grid-cols-1 gap-3">
                            <li
                                v-for="perm in pendingPhotoPermissions"
                                :key="perm.id"
                                class="p-3 rounded border border-white/20 bg-white/5 flex items-center gap-3"
                            >
                                <img
                                    v-if="perm.thumb"
                                    :src="perm.thumb"
                                    class="w-20 h-14 object-cover rounded"
                                />
                                <div class="flex-1">
                                    <div class="text-sm">
                                        申請者:
                                        <span class="opacity-90"
                                            >{{ perm.viewer?.name }} (ID:
                                            {{ perm.viewer?.id }})</span
                                        >
                                    </div>
                                    <div class="text-xs opacity-80">
                                        メッセージ:
                                        {{ perm.message || "（なし）" }}
                                    </div>
                                    <div class="text-[11px] opacity-60">
                                        申請日時: {{ perm.created_at }}
                                    </div>
                                </div>
                                <div class="shrink-0 space-x-2">
                                    <button
                                        type="button"
                                        @click="approvePhoto(perm)"
                                        class="bg-green-600 text-white rounded px-3 py-1 text-sm"
                                    >
                                        承認
                                    </button>
                                    <button
                                        type="button"
                                        @click="denyPhoto(perm)"
                                        class="bg-gray-500 text-white rounded px-3 py-1 text-sm"
                                    >
                                        否認
                                    </button>
                                </div>
                            </li>
                        </ul>
                        <div class="mt-1 text-xs opacity-70">
                            ※ 写真の承認は、その写真のみ非ぼかし表示になります。
                        </div>
                    </div>
                    <!-- =========================================== -->

                    <!-- ================= プロフィール編集 ================= -->
                    <form @submit.prevent="submit" class="space-y-5">
                        <div>
                            <label class="block mb-1 text-sm"
                                >ニックネーム</label
                            >
                            <input
                                v-model="form.nickname"
                                type="text"
                                class="w-full h-11 rounded-md px-3 text-black"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block mb-1 text-sm">ランク</label>
                                <input
                                    v-model.number="form.rank"
                                    type="number"
                                    min="0"
                                    max="99"
                                    class="w-full h-11 rounded-md px-3 text-black"
                                />
                            </div>
                            <div>
                                <label class="block mb-1 text-sm">年齢</label>
                                <input
                                    v-model.number="form.age"
                                    type="number"
                                    min="18"
                                    max="99"
                                    class="w-full h-11 rounded-md px-3 text-black"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block mb-1 text-sm"
                                    >身長(cm)</label
                                >
                                <input
                                    v-model.number="form.height_cm"
                                    type="number"
                                    min="120"
                                    max="220"
                                    class="w-full h-11 rounded-md px-3 text-black"
                                />
                            </div>
                            <div>
                                <label class="block mb-1 text-sm">カップ</label>
                                <input
                                    v-model="form.cup"
                                    type="text"
                                    placeholder="A〜H等"
                                    class="w-full h-11 rounded-md px-3 text-black"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block mb-1 text-sm">エリア</label>
                            <select
                                v-model="form.area"
                                class="w-full h-11 rounded-md px-3 text-black"
                            >
                                <option value="">選択してください</option>
                                <option>北海道・東北</option>
                                <option>関東</option>
                                <option>中部</option>
                                <option>近畿</option>
                                <option>中国・四国</option>
                                <option>九州・沖縄</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block mb-1 text-sm"
                                    >スタイル</label
                                >
                                <select
                                    v-model="form.style"
                                    class="w-full h-11 rounded-md px-3 text-black"
                                >
                                    <option value="">未選択</option>
                                    <option>スレンダー</option>
                                    <option>細身</option>
                                    <option>グラマー</option>
                                    <option>その他</option>
                                </select>
                            </div>
                            <div>
                                <label class="block mb-1 text-sm">お酒</label>
                                <select
                                    v-model="form.alcohol"
                                    class="w-full h-11 rounded-md px-3 text-black"
                                >
                                    <option value="">未選択</option>
                                    <option>飲む</option>
                                    <option>少し</option>
                                    <option>飲まない</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block mb-1 text-sm">MBTI</label>
                            <input
                                v-model="form.mbti"
                                maxlength="4"
                                placeholder="ENFPなど"
                                class="w-full h-11 rounded-md px-3 text-black uppercase"
                            />
                        </div>

                        <div>
                            <label class="block mb-2 text-sm">タグ</label>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="t in available_tags"
                                    :key="t.id"
                                    type="button"
                                    @click="toggleTagId(t.id)"
                                    :class="
                                        form.tag_ids.includes(t.id)
                                            ? 'bg-yellow-200 text-black'
                                            : 'bg-white/20'
                                    "
                                    class="px-3 py-1 rounded-full text-sm"
                                >
                                    {{ t.name }}
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block mb-1 text-sm">自己紹介</label>
                            <textarea
                                v-model="form.freeword"
                                rows="4"
                                class="w-full rounded-md px-3 py-2 text-black"
                            ></textarea>
                        </div>

                        <!-- =============== 写真管理 =============== -->
                        <div class="pt-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold">写真</h3>
                                <label
                                    class="px-3 py-1 rounded bg-yellow-200 text-black cursor-pointer text-sm"
                                >
                                    追加
                                    <input
                                        type="file"
                                        accept="image/*"
                                        multiple
                                        class="hidden"
                                        @change="onAddPhotos"
                                    />
                                </label>
                            </div>

                            <div
                                v-if="existing.length"
                                class="grid grid-cols-3 gap-3"
                            >
                                <div
                                    v-for="(ph, idx) in existing"
                                    :key="ph.id"
                                    class="relative border border-white/20 rounded overflow-hidden"
                                >
                                    <div
                                        class="w-full max-h-48 bg-black/30 flex items-center justify-center"
                                    >
                                        <img
                                            :src="ph.url"
                                            class="max-h-48 w-full object-contain transition"
                                            :class="ph._blur ? 'blur-sm' : ''"
                                        />
                                    </div>

                                    <div
                                        class="absolute top-1 left-1 flex gap-1"
                                    >
                                        <button
                                            type="button"
                                            @click="move(idx, -1)"
                                            class="px-1 py-0.5 text-xs bg-black/50 text-white rounded"
                                        >
                                            ↑
                                        </button>
                                        <button
                                            type="button"
                                            @click="move(idx, 1)"
                                            class="px-1 py-0.5 text-xs bg-black/50 text-white rounded"
                                        >
                                            ↓
                                        </button>
                                    </div>

                                    <div class="absolute top-1 right-1">
                                        <button
                                            type="button"
                                            :class="[
                                                'px-1 py-0.5 text-xs rounded',
                                                ph.id === primaryId &&
                                                !ph._delete
                                                    ? 'bg-amber-400 text-black'
                                                    : 'bg-black/50 text-white',
                                            ]"
                                            @click="setPrimary(ph)"
                                        >
                                            ★
                                        </button>
                                    </div>

                                    <div class="absolute bottom-1 left-1">
                                        <button
                                            type="button"
                                            @click="toggleBlur(ph)"
                                            :disabled="!ph.id || ph._delete"
                                            :class="[
                                                'px-1.5 py-0.5 text-[11px] rounded',
                                                !ph.id || ph._delete
                                                    ? 'bg-black/30 text-white/50 cursor-not-allowed'
                                                    : ph._blur
                                                    ? 'bg-black/70 text-yellow-200 ring-1 ring-yellow-300'
                                                    : 'bg-black/40 text-white',
                                            ]"
                                        >
                                            {{
                                                ph._blur
                                                    ? "ぼかしON"
                                                    : "ぼかしOFF"
                                            }}
                                        </button>
                                    </div>

                                    <button
                                        type="button"
                                        class="absolute bottom-1 right-1 px-1.5 py-0.5 text-xs bg-red-600 text-white rounded"
                                        @click="toggleDelete(ph)"
                                    >
                                        {{ ph._delete ? "復活" : "削除" }}
                                    </button>
                                </div>
                            </div>

                            <div v-if="newFiles.length" class="mt-2">
                                <div class="text-sm opacity-80 mb-1">
                                    追加予定（保存で反映）
                                </div>
                                <div class="flex flex-wrap gap-3">
                                    <div
                                        v-for="(f, i) in newFiles"
                                        :key="i"
                                        class="w-24 h-24 border border-white/20 rounded overflow-hidden"
                                    >
                                        <img
                                            :src="getPreviewUrl(f)"
                                            class="w-full h-full object-cover"
                                            @load="
                                                revokePreviewUrl(
                                                    $event.target.src
                                                )
                                            "
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- ========================================== -->
                        <!-- スケジュールボタン追加 -->
                        <div class="pt-6">
                            <Link
                                :href="`/casts/${form.id}/schedule`"
                                class="w-full block text-center h-12 leading-[3rem] rounded-md font-bold tracking-[0.5em] bg-gradient-to-r from-[#caa14b] to-[#f0e1b1] text-black border border-[#b28933] shadow hover:brightness-110"
                            >
                                スケジュールを作成する
                            </Link>
                        </div>
                        <div class="pt-4">
                            <button
                                :disabled="form.processing"
                                class="w-full h-12 rounded-md font-bold tracking-[0.5em] bg-[#7a560f] text-white border border-[#c79a2b] shadow hover:brightness-110"
                            >
                                更　新
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
