<!-- resources/js/Pages/Cast/Show.vue -->
<script setup>
import { computed, ref, watch, onMounted, defineComponent } from "vue";
import { router, Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import InfoRow from "@/Components/InfoRow.vue";

const props = defineProps({
    cast: { type: Object, required: true },
    schedule: { type: Array, default: () => [] },
    unblur: {
        type: Object,
        default: () => ({ requested: false, status: null }),
    },
    gifts: { type: Array, default: () => [] },
    my_balance: { type: Number, default: 0 },
    last_gift_id: { type: Number, default: null },
});

onMounted(() => {
    console.log("🎯 cast props:", props.cast);
});

/** route() が無くても動くフォールバック */
const urlFor = (name, params = {}, fallback = "") => {
    try {
        if (typeof route === "function") {
            const u = route(name, params);
            if (typeof u === "string" && u.length) return u;
        }
    } catch {}
    return fallback;
};

/* ====== 写真 ====== */
const gallery = computed(() =>
    Array.isArray(props.cast?.photos) ? props.cast.photos : []
);
const photoPathUrl = computed(() =>
    props.cast?.photo_path ? `/storage/${props.cast.photo_path}` : null
);

/** current を選ぶ優先順位: primary → photo_path と一致 → 先頭 */
const pickCurrent = (arr) => {
    if (!arr?.length) return null;
    const pri = arr.find((p) => p.is_primary);
    if (pri) return pri;
    if (photoPathUrl.value) {
        const byPath = arr.find((p) => p.url === photoPathUrl.value);
        if (byPath) return byPath;
    }
    return arr[0];
};
const current = ref(pickCurrent(gallery.value));

/** props 更新に追従 */
watch(gallery, (photos) => {
    const arr = photos ?? [];
    if (!current.value) {
        current.value = pickCurrent(arr);
        return;
    }
    const updated = arr.find((p) => p.id === current.value.id);
    current.value = updated ?? pickCurrent(arr);
});

/* ====== ぼかし判定 ====== */
const hasProfileAccess = computed(() => !!props.cast?.viewer_has_unblur_access);
const photoAllowed = (p) => {
    const u = p?.unblur ?? {};
    return (
        hasProfileAccess.value || u.granted === true || u.status === "approved"
    );
};
const photoShouldBlur = (p) => p?.should_blur === true && !photoAllowed(p);
const shouldBlur = computed(() => {
    const cur = current.value;
    if (!cur) return false;
    if (cur.is_primary) return false;
    return photoShouldBlur(cur);
});

/* ====== ぼかし解除申請 ====== */
const requestingPhoto = ref({});
const requestUnblurPhoto = (photoId) => {
    if (requestingPhoto.value[photoId]) return;
    requestingPhoto.value = { ...requestingPhoto.value, [photoId]: true };
    router.post(
        `/photos/${photoId}/unblur-requests`,
        {},
        {
            onFinish: () => {
                requestingPhoto.value = {
                    ...requestingPhoto.value,
                    [photoId]: false,
                };
            },
        }
    );
};

/* ====== チャット ====== */
const startChatHref = computed(() => `/casts/${props.cast.id}/start-chat`);

/* ====== ギフト ====== */
const showGift = ref(false);
const sendForm = useForm({
    cast_id: props.cast.id,
    gift_id: null,
    message: "",
});
const gi = ref(0);
const curGift = computed(() => props.gifts?.[gi.value] ?? null);
const sendingGift = ref(false);
const giftError = ref("");
const giftToast = ref("");
const canSend = (g) => {
    if (!g) return false;
    if (props.my_balance < g.present_points) return false;
    if (props.last_gift_id && props.last_gift_id === g.id) return false;
    return true;
};

function send(g) {
  console.log("🎁 SENDクリック", g)
  if (!canSend(g)) {
    if (props.my_balance < g.present_points) {
      alert("ポイントが不足しています。チャージが必要です。")
    } else if (props.last_gift_id === g.id) {
      alert("同じギフトは連続で贈れません。")
    } else {
      alert("送信条件を満たしていません。")
    }
    return
  }
  if (!canSend(g)) {
    console.warn("❌ canSend=false", {
      balance: props.my_balance,
      present_points: g.present_points,
      last_gift_id: props.last_gift_id,
    })
    return
  }

  sendingGift.value = true
  giftError.value = ""
  sendForm.cast_id = props.cast.id
  sendForm.gift_id = g.id

  console.log("📤 POST開始", sendForm)

  sendForm.post("/gifts/send", {
    preserveScroll: true,
    onFinish: () => {
      console.log("✅ onFinish発火")
      sendingGift.value = false
    },
    onSuccess: (res) => {
      console.log("✅ onSuccess", res)
      showGift.value = false
      sendForm.reset("message")
      giftToast.value = "🎁 贈りました"
      setTimeout(() => (giftToast.value = ""), 2500)
      router.reload({ only: ["my_balance", "last_gift_id"] })
    },
    onError: (errs) => {
      console.error("💥 onError", errs)
      giftError.value = errs?.gift || "送信に失敗しました。"
    },
  })
}

function nextGift() {
    if (!props.gifts?.length) return;
    gi.value = (gi.value + 1) % props.gifts.length;
}
function prevGift() {
    if (!props.gifts?.length) return;
    gi.value = (gi.value - 1 + props.gifts.length) % props.gifts.length;
}
</script>

<template>
    <AppLayout>
        <div
            class="pt-4 pb-28 px-4 text-white/90 bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%]"
        >
            <!-- 顔写真 + 名前 -->
            <section
                class="mx-auto max-w-[780px] bg-[#2b241b]/60 rounded-lg border border-[#d1b05a]/50 p-3"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span
                            class="inline-block w-3 h-3 rounded-full bg-green-400"
                        ></span>
                        <div class="text-xl font-semibold tracking-wide">
                            {{ props.cast.nickname ?? "name" }}
                        </div>
                    </div>
                    <img
                        src="/assets/icons/like-badge.png"
                        class="h-8"
                        alt="like"
                    />
                </div>

                <!-- メイン写真 -->
                <div
                    class="mt-2 relative bg-white rounded overflow-hidden ring-1 ring-black/10 flex items-center justify-center"
                    style="--maxh: 52vh"
                >
                    <img
                        :src="
                            current
                                ? current.url
                                : props.cast.photo_path
                                ? `/storage/${props.cast.photo_path}`
                                : '/assets/imgs/placeholder.png'
                        "
                        class="img-natural-fit transition"
                        :class="shouldBlur ? 'blur-lg' : ''"
                        draggable="false"
                        alt="main"
                    />
                    <div
                        v-if="shouldBlur"
                        class="absolute top-2 left-2 bg-black/45 text-white text-xs px-2 py-1 rounded"
                    >
                        🔒 ぼかし中
                    </div>
                </div>

                <!-- サムネ（横スクロール） -->
                <div v-if="gallery.length" class="mt-3 relative">
                    <div
                        class="flex gap-3 overflow-x-auto no-scrollbar -mx-2 px-2 py-1"
                    >
                        <div
                            v-for="p in gallery"
                            :key="p.id"
                            class="shrink-0 w-28 h-20 rounded overflow-hidden ring-1 ring-black/20 relative cursor-pointer bg-black/20"
                            @click="current = p"
                        >
                            <img
                                :src="p.url"
                                class="img-no-crop transition"
                                :class="
                                    photoShouldBlur(p)
                                        ? 'blur-md scale-[1.03]'
                                        : ''
                                "
                            />
                            <div
                                v-if="
                                    photoShouldBlur(p) && !p.unblur?.requested
                                "
                                class="absolute inset-0 flex items-center justify-center bg-black/35 z-10"
                            >
                                <button
                                    class="px-2 py-1 text-xs rounded bg-yellow-200 text-black disabled:opacity-60"
                                    :disabled="requestingPhoto[p.id]"
                                    @click.stop="requestUnblurPhoto(p.id)"
                                >
                                    申請
                                </button>
                            </div>
                            <div
                                v-else-if="
                                    photoShouldBlur(p) && p.unblur?.requested
                                "
                                class="absolute bottom-1 right-1 text-[10px] bg-black/55 text-white px-1 rounded z-10"
                            >
                                申請済
                            </div>

                            <div
                                v-if="current && current.id === p.id"
                                class="absolute inset-0 ring-2 ring-yellow-300 rounded pointer-events-none"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- 星とアクション -->
                <div class="mt-2 flex items-center justify-between">
                    <div class="text-[#ffcc66]">★ ★ ★ ★ ☆</div>
                    <button
                        @click="(gi = 0), (showGift = true)"
                        class="px-4 py-2 rounded bg-pink-600 text-white shadow"
                    >
                        🎁 ギフトを贈る
                    </button>
                </div>
            </section>

            <!-- スケジュール -->
            <section class="mx-auto max-w-[780px] mt-6">
                <div
                    class="text-center text-lg bg-[#6b4b17] border border-[#d1b05a] py-1 rounded"
                >
                    スケジュール
                </div>
                <div class="mt-3 grid grid-cols-7 gap-1 text-center text-sm">
                    <div
                        v-for="d in props.schedule"
                        :key="d.date"
                        class="bg-[#2b241b]/60 rounded border border-[#d1b05a]/30 p-2"
                    >
                        <div class="text-xs opacity-80">{{ d.date }}</div>
                        <div class="opacity-80">{{ d.weekday }}</div>
                        <div
                            class="mt-2 text-yellow-200 text-xs"
                            v-if="d.slots?.length"
                        >
                            <div v-for="(s, i) in d.slots" :key="i">
                                {{ s.start }} - {{ s.end }}
                            </div>
                        </div>
                        <div class="mt-4 text-xs opacity-50" v-else>未設定</div>
                    </div>
                </div>
            </section>

            <!-- プロフィール表 -->
            <section class="mx-auto max-w-[780px] mt-8">
                <div class="grid grid-cols-2 gap-2">
                    <InfoRow
                        label="ニックネーム"
                        :value="props.cast.nickname || '—'"
                    />
                    <InfoRow label="エリア" :value="props.cast.area || '—'" />
                    <InfoRow
                        label="身長"
                        :value="
                            props.cast.height_cm
                                ? props.cast.height_cm + ' cm'
                                : '—'
                        "
                    />
                    <InfoRow
                        label="年齢"
                        :value="props.cast.age ? props.cast.age + ' 歳' : '—'"
                    />
                    <InfoRow
                        label="スタイル"
                        :value="props.cast.style || '—'"
                    />
                    <InfoRow label="カップ" :value="props.cast.cup || '—'" />
                    <InfoRow label="お酒" :value="props.cast.alcohol || '—'" />
                    <InfoRow label="MBTI" :value="props.cast.mbti || '—'" />
                </div>

                <div class="mt-6">
                    <div class="text-sm opacity-80 mb-1">自己紹介</div>
                    <div
                        class="rounded bg-[#2b241b]/60 border border-[#d1b05a]/30 p-3 min-h-[120px]"
                    >
                        {{ props.cast.freeword || "—" }}
                    </div>
                </div>

                <div class="mt-6">
                    <div class="text-sm opacity-80 mb-2">タグ</div>
                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="t in props.cast.tags || []"
                            :key="t"
                            class="px-3 py-1 rounded-full bg-[#ffe09a] text-black text-xs shadow"
                            >{{ t }}</span
                        >
                        <span
                            v-if="!(props.cast.tags && props.cast.tags.length)"
                            class="opacity-60 text-sm"
                            >—</span
                        >
                    </div>
                </div>
            </section>
        </div>

        <!-- 固定CTA -->
        <div
            class="fixed z-[60] pointer-events-none right-4"
            :style="{
                bottom: 'calc(env(safe-area-inset-bottom, 0px) + 5.5rem)',
            }"
        >
            <Link
                as="button"
                method="post"
                :href="startChatHref"
                class="pointer-events-auto h-10 px-3 rounded-full bg-[#e7d7a0] text-black text-sm font-medium shadow-[0_6px_18px_rgba(0,0,0,.28)] border border-black/10 hover:brightness-105 active:translate-y-[1px] transition flex items-center gap-2"
            >
                <img src="/assets/icons/message.png" alt="" class="h-5 w-5" />
                メッセージ
            </Link>
        </div>
        <!-- 🎁 ギフト選択モーダル -->
        <div
            v-if="showGift"
            class="fixed inset-0 bg-black/70 z-[70] flex items-center justify-center p-4"
        >
            <div
                class="bg-white rounded-lg p-5 max-w-sm w-full relative text-center shadow-lg text-black"
            >
                <!-- 閉じる -->
                <button
                    class="absolute top-2 right-2 text-gray-500 hover:text-black"
                    @click="showGift = false"
                >
                    ✕
                </button>

                <!-- ギフトが存在する場合 -->
                <div v-if="curGift" class="space-y-4">
                    <h2 class="text-xl font-semibold text-gray-800">
                        {{ curGift.name }}
                    </h2>

                    <img
                        :src="curGift.image_url"
                        class="mx-auto w-40 h-40 object-contain rounded"
                        alt="gift"
                    />

                    <div class="text-gray-700 text-sm">
                        消費ポイント：
                        <span class="font-bold text-pink-600">
                            {{ curGift.present_points }}
                        </span>
                        pt
                    </div>

                    <div class="flex justify-center gap-3 mt-4">
                        <button
                            @click="prevGift"
                            class="px-3 py-2 bg-gray-200 rounded hover:bg-gray-300"
                        >
                            ← 前へ
                        </button>
                        <button
                            @click="nextGift"
                            class="px-3 py-2 bg-gray-200 rounded hover:bg-gray-300"
                        >
                            次へ →
                        </button>
                    </div>

                    <!-- メッセージ欄 -->
                    <div class="mt-4">
                        <textarea
                            v-model="sendForm.message"
                            class="w-full rounded border p-2 text-sm"
                            rows="2"
                            placeholder="メッセージ（任意）"
                        ></textarea>
                    </div>

                    <!-- 送信ボタン -->
                    <div class="mt-5">
<button
  @click="send(curGift)"
  class="px-4 py-2 rounded bg-pink-600 text-white font-semibold hover:brightness-110"
>
  🎁 このギフトを贈る
</button>
                    </div>

                    <!-- メッセージ表示 -->
                    <div v-if="giftError" class="text-red-500 text-sm mt-2">
                        {{ giftError }}
                    </div>
                    <div
                        v-if="giftToast"
                        class="text-green-600 font-semibold mt-2 transition-opacity duration-500"
                    >
                        {{ giftToast }}
                    </div>
                </div>

                <!-- ギフトが空 -->
                <div v-else class="text-gray-500 text-sm mt-4">
                    ギフトがありません。
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.img-natural-fit {
    max-height: var(--maxh, 52vh);
    width: auto;
    height: auto;
    max-width: 100%;
    object-fit: contain;
}
.img-no-crop {
    width: 100%;
    height: 100%;
    object-fit: contain;
}
</style>
