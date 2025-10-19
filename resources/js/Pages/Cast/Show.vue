<!-- resources/js/Pages/Cast/Show.vue -->
<script setup>
import { computed, ref, watch } from "vue"
import { router, Link, useForm } from "@inertiajs/vue3"
import AppLayout from "@/Layouts/AppLayout.vue"

/** route() が無くても動くフォールバック */
const urlFor = (name, params = {}, fallback = "") => {
  try {
    if (typeof route === "function") {
      const u = route(name, params)
      if (typeof u === "string" && u.length) return u
    }
  } catch {}
  return fallback
}

const props = defineProps({
  // cast.photos: [{ id, url, sort_order, is_primary:boolean, should_blur:boolean, unblur?: {granted?:bool,status?:'approved'|'pending'} }]
  cast: { type: Object, required: true },
  schedule: { type: Array, default: () => [] },
  unblur: { type: Object, default: () => ({ requested:false, status:null }) },

  // ★ ギフト送付用（サーバで渡す）
  gifts: { type: Array, default: () => [] },         // [{id,name,image_url,present_points,cast_points}, ...]
  my_balance: { type: Number, default: 0 },           // ログインユーザー残高
  last_gift_id: { type: Number, default: null },      // 相手への直近ギフトID（連投防止用）
})

/* ====== 写真 ====== */
const gallery = computed(() => Array.isArray(props.cast?.photos) ? props.cast.photos : [])
const photoPathUrl = computed(() =>
  props.cast?.photo_path ? `/storage/${props.cast.photo_path}` : null
)

/** current を選ぶ優先順位: primary → photo_path と一致 → 先頭 */
const pickCurrent = (arr) => {
  if (!arr?.length) return null
  const pri = arr.find(p => p.is_primary)
  if (pri) return pri
  if (photoPathUrl.value) {
    const byPath = arr.find(p => p.url === photoPathUrl.value)
    if (byPath) return byPath
  }
  return arr[0]
}
const current = ref(pickCurrent(gallery.value))

/** props 更新に追従 */
watch(gallery, (photos) => {
  const arr = photos ?? []
  if (!current.value) {
    current.value = pickCurrent(arr)
    return
  }
  const updated = arr.find(p => p.id === current.value.id)
  current.value = updated ?? pickCurrent(arr)
})

/* ====== ぼかし判定 ====== */
const hasProfileAccess = computed(() => !!props.cast?.viewer_has_unblur_access)
const photoAllowed = (p) => {
  const u = p?.unblur ?? {}
  return hasProfileAccess.value || u.granted === true || u.status === 'approved'
}
const photoShouldBlur = (p) => p?.should_blur === true && !photoAllowed(p)
const shouldBlur = computed(() => {
  const cur = current.value
  if (!cur) return false
  if (cur.is_primary) return false
  return photoShouldBlur(cur)
})

/* ====== ぼかし解除申請 ====== */
const hasUnblurRequest = computed(() => !!props.unblur?.requested)
const unblurStatus = computed(() => props.unblur?.status ?? null)

const requesting = ref(false)
const requestUnblurProfile = () => {
  if (requesting.value) return
  requesting.value = true
  router.post(`/casts/${props.cast.id}/unblur-requests`, {}, {
    onFinish: () => { requesting.value = false }
  })
}

const requestingPhoto = ref({})
const requestUnblurPhoto = (photoId) => {
  if (requestingPhoto.value[photoId]) return
  requestingPhoto.value = { ...requestingPhoto.value, [photoId]: true }
  router.post(`/photos/${photoId}/unblur-requests`, {}, {
    onFinish: () => {
      requestingPhoto.value = { ...requestingPhoto.value, [photoId]: false }
    }
  })
}

/* ====== チャット開始（既存CTA用） ====== */
const startingChat = ref(false)
const startChat = () => {
  if (startingChat.value) return
  startingChat.value = true
  router.post(
    urlFor('casts.startChat', props.cast.id, `/casts/${props.cast.id}/start-chat`),
    {},
    { onFinish: () => { startingChat.value = false } }
  )
}
const startChatHref = computed(() => `/casts/${props.cast.id}/start-chat`)

/* ====== ギフト送付（1件選択） ====== */
const showGift = ref(false)
const sendForm = useForm({ cast_id: props.cast.id, gift_id: null, message: '' })
const gi = ref(0) // index
const curGift = computed(() => props.gifts?.[gi.value] ?? null)

const canSend = (g) => {
  if (!g) return false
  if (props.my_balance < g.present_points) return false
  if (props.last_gift_id && props.last_gift_id === g.id) return false // 直前同一は不可
  return true
}
const sendingGift = ref(false)
const giftError = ref('')
const giftToast = ref('') // 成功トースト

function send(g) {
  if (!canSend(g) || sendingGift.value) return
  sendingGift.value = true
  giftError.value = ''
  sendForm.cast_id = props.cast.id
  sendForm.gift_id = g.id
  sendForm.post('/gifts/send', {
    preserveScroll: true,
    onFinish: () => { sendingGift.value = false },
    onSuccess: () => {
      // UI反応：閉じる + メッセージクリア + トースト + 残高/直近ギフト再取得
      showGift.value = false
      sendForm.reset('message')
      giftToast.value = '🎁 贈りました'
      setTimeout(() => (giftToast.value = ''), 2500)
      router.reload({ only: ['my_balance','last_gift_id'] })
    },
    onError: (errs) => {
      giftError.value = errs?.gift || '送信に失敗しました。'
    },
  })
}
function nextGift(){ if (!props.gifts?.length) return; gi.value = (gi.value + 1) % props.gifts.length }
function prevGift(){ if (!props.gifts?.length) return; gi.value = (gi.value - 1 + props.gifts.length) % props.gifts.length }
</script>

<template>
  <AppLayout>
    <div class="pt-4 pb-28 px-4 text-white/90 bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%]">

      <!-- 顔写真 + 名前 -->
      <section class="mx-auto max-w-[780px] bg-[#2b241b]/60 rounded-lg border border-[#d1b05a]/50 p-3">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="inline-block w-3 h-3 rounded-full bg-green-400"></span>
            <div class="text-xl font-semibold tracking-wide">
              {{ props.cast.nickname ?? "name" }}
            </div>
          </div>
          <img src="/assets/icons/like-badge.png" class="h-8" alt="like"/>
        </div>

        <!-- メイン写真 -->
        <div
          class="mt-2 relative bg-white rounded overflow-hidden ring-1 ring-black/10 flex items-center justify-center"
          style="--maxh: 52vh;"
        >
          <img
            :src="current
                    ? current.url
                    : (props.cast.photo_path ? `/storage/${props.cast.photo_path}` : '/assets/imgs/placeholder.png')"
            class="img-natural-fit transition"
            :class="shouldBlur ? 'blur-lg' : ''"
            draggable="false"
            alt="main"
          />
          <div v-if="shouldBlur" class="absolute top-2 left-2 bg-black/45 text-white text-xs px-2 py-1 rounded">
            🔒 ぼかし中
          </div>
        </div>

        <!-- サムネ（横スクロール） -->
        <div v-if="gallery.length" class="mt-3 relative">
          <div class="flex gap-3 overflow-x-auto no-scrollbar -mx-2 px-2 py-1">
            <div
              v-for="p in gallery" :key="p.id"
              class="shrink-0 w-28 h-20 rounded overflow-hidden ring-1 ring-black/20 relative cursor-pointer bg-black/20"
              @click="current = p" role="button" tabindex="0"
            >
              <img
                :src="p.url"
                class="img-no-crop transition"
                :class="photoShouldBlur(p) ? 'blur-md scale-[1.03]' : ''"
                alt=""
              />
              <!-- 個別申請 -->
              <div v-if="photoShouldBlur(p) && !(p.unblur?.requested)"
                   class="absolute inset-0 flex items-center justify-center bg-black/35 z-10">
                <button
                  class="px-2 py-1 text-xs rounded bg-yellow-200 text-black disabled:opacity-60"
                  :disabled="requestingPhoto[p.id]"
                  @click.stop="requestUnblurPhoto(p.id)"
                >
                  申請
                </button>
              </div>
              <div v-else-if="photoShouldBlur(p) && p.unblur?.requested"
                   class="absolute bottom-1 right-1 text-[10px] bg-black/55 text-white px-1 rounded z-10">
                申請済
              </div>

              <div v-if="current && current.id===p.id"
                   class="absolute inset-0 ring-2 ring-yellow-300 rounded pointer-events-none"></div>
            </div>
          </div>
        </div>

        <!-- 星とアクション -->
        <div class="mt-2 flex items-center justify-between">
          <div class="text-[#ffcc66]">★ ★ ★ ★ ☆</div>

          <div class="flex items-center gap-3">
            <!-- ★ ギフトモーダルを開く -->
            <button @click="(gi = 0, showGift = true)" class="px-4 py-2 rounded bg-pink-600 text-white shadow">
              🎁 ギフトを贈る
            </button>
          </div>
        </div>
      </section>

      <!-- スケジュール -->
      <section class="mx-auto max-w-[780px] mt-6">
        <div class="text-center text-lg bg-[#6b4b17] border border-[#d1b05a] py-1 rounded">スケジュール</div>
        <div class="mt-3 grid grid-cols-7 gap-1 text-center text-sm">
          <div v-for="d in props.schedule" :key="d.date"
               class="bg-[#2b241b]/60 rounded border border-[#d1b05a]/30 p-2">
            <div class="text-xs opacity-80">{{ d.date }}</div>
            <div class="opacity-80">{{ d.weekday }}</div>
            <div class="mt-2 text-yellow-200 text-xs" v-if="d.slots?.length">
              <div v-for="(s, i) in d.slots" :key="i">{{ s.start }} - {{ s.end }}</div>
            </div>
            <div class="mt-4 text-xs opacity-50" v-else>未設定</div>
          </div>
        </div>
      </section>

      <!-- プロフィール表 -->
      <section class="mx-auto max-w-[780px] mt-8">
        <div class="grid grid-cols-2 gap-2">
          <InfoRow label="エリア" :value="props.cast.area" />
          <InfoRow label="身長" :value="props.cast.height_cm ? props.cast.height_cm + ' cm' : ''" />
          <InfoRow label="年齢" :value="props.cast.age ? props.cast.age + ' 歳' : ''" />
          <InfoRow label="カップ" :value="props.cast.cup" />
          <InfoRow label="スタイル" :value="props.cast.style" />
          <InfoRow label="お酒" :value="props.cast.alcohol" />
          <InfoRow label="MBTI" :value="props.cast.mbti" />
        </div>

        <div class="mt-6">
          <div class="text-sm opacity-80 mb-1">自己紹介</div>
          <div class="rounded bg-[#2b241b]/60 border border-[#d1b05a]/30 p-3 min-h-[120px]">
            {{ props.cast.freeword || "—" }}
          </div>
        </div>

        <div class="mt-6">
          <div class="text-sm opacity-80 mb-2">タグ</div>
          <div class="flex flex-wrap gap-2">
            <span v-for="t in props.cast.tags || []" :key="t"
                  class="px-3 py-1 rounded-full bg-[#ffe09a] text-black text-xs shadow">{{ t }}</span>
            <span v-if="!(props.cast.tags && props.cast.tags.length)" class="opacity-60 text-sm">—</span>
          </div>
        </div>
      </section>
    </div>

    <!-- 固定CTA -->
    <div class="fixed z-[60] pointer-events-none right-4"
         :style="{ bottom: 'calc(env(safe-area-inset-bottom, 0px) + 5.5rem)' }">
      <Link
        as="button"
        method="post"
        :href="startChatHref"
        class="pointer-events-auto h-10 px-3 rounded-full bg-[#e7d7a0] text-black text-sm font-medium
               shadow-[0_6px_18px_rgba(0,0,0,.28)] border border-black/10 hover:brightness-105
               active:translate-y-[1px] transition flex items-center gap-2"
      >
        <img src="/assets/icons/message.png" alt="" class="h-5 w-5" />
        メッセージ
      </Link>
    </div>
    <div class="fixed z-[60] pointer-events-none left-4"
         :style="{ bottom: 'calc(env(safe-area-inset-bottom, 0px) + 5.5rem)' }">
      <Link
        as="button"
        method="post"
        :href="startChatHref"
        class="pointer-events-auto h-10 px-3 rounded-full bg-[#e7d7a0] text-black text-sm font-medium
               shadow-[0_6px_18px_rgba(0,0,0,.28)] border border-black/10 hover:brightness-105
               active:translate-y-[1px] transition flex items-center gap-2"
      >
        <img src="/assets/icons/message.png" alt="" class="h-5 w-5" />
        指名する
      </Link>
    </div>

    <!-- 🎁 ギフトモーダル -->
    <div v-if="showGift" class="fixed inset-0 z-[80] bg-black/50 flex items-center justify-center p-3">
      <div class="bg-white rounded-2xl p-4 w-[min(760px,95vw)] max-h-[90vh] overflow-auto text-gray-800">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold">ギフトを贈る</h3>
          <button class="text-gray-500" @click="showGift=false">×</button>
        </div>

        <div class="mt-1 text-sm">
          残高：<span class="font-bold">{{ (props.my_balance||0).toLocaleString() }}</span> pt
        </div>

        <!-- 1件だけ表示 + 左右ナビ + ドット -->
        <div class="mt-3">
          <div class="flex items-center justify-between">
            <button class="px-2 py-1 rounded border text-sm" @click="prevGift" :disabled="!props.gifts?.length">＜</button>
            <div class="text-sm">残高：<b>{{ (props.my_balance||0).toLocaleString() }}</b> pt</div>
            <button class="px-2 py-1 rounded border text-sm" @click="nextGift" :disabled="!props.gifts?.length">＞</button>
          </div>

          <div v-if="curGift" class="mt-3 p-3 rounded border">
            <div class="flex gap-3">
              <img :src="curGift.image_url" class="h-20 w-24 object-contain bg-gray-50 rounded" />
              <div class="flex-1">
                <div class="font-semibold text-lg">{{ curGift.name }}</div>
                <div class="text-xs text-gray-600 mt-1">
                  🧧 {{ curGift.present_points.toLocaleString() }} → 🎁 {{ curGift.cast_points.toLocaleString() }}
                </div>
                <div v-if="props.last_gift_id===curGift.id" class="text-[11px] text-rose-600 mt-1">
                  直前と同じギフトは続けて送れません
                </div>
              </div>
            </div>

            <div class="mt-3 flex gap-2">
              <input v-model="sendForm.message" placeholder="メッセージ（任意）"
                     class="flex-1 px-2 py-1 border rounded text-sm text-black placeholder-gray-400" />
              <button class="px-4 py-2 rounded text-white"
                      :class="canSend(curGift)?'bg-pink-600 hover:brightness-110':'bg-gray-400'"
                      :disabled="!canSend(curGift) || sendingGift"
                      @click="send(curGift)">
                <span v-if="!sendingGift">送る</span>
                <span v-else class="opacity-80">送信中…</span>
              </button>
            </div>

            <div v-if="giftError" class="mt-2 text-xs text-rose-600">
              {{ giftError }}
            </div>
          </div>

          <!-- ドットインジケータ -->
          <div class="mt-3 flex justify-center gap-2">
            <button v-for="(g,i) in props.gifts" :key="g.id"
                    class="h-2.5 w-2.5 rounded-full transition"
                    :class="i===gi ? 'bg-gray-800' : 'bg-gray-300 hover:bg-gray-400'"
                    @click="gi = i"
                    :aria-label="g.name"></button>
          </div>
        </div>

        <div class="mt-3 text-xs text-gray-500">
          ※ プレゼントポイントが不足している場合は送付できません。  
          ※ 同じ相手に同じギフトを<strong>連続で</strong>送ることはできません。
        </div>
      </div>
    </div>

    <!-- ✅ 送信成功トースト -->
    <div v-if="giftToast"
         class="fixed z-[90] right-4 bottom-5 bg-black/80 text-white text-sm px-3 py-2 rounded shadow">
      {{ giftToast }}
    </div>
  </AppLayout>
</template>

<script>
export default {
  components: {
    InfoRow: {
      props: { label: String, value: String },
      template: `
        <div class="bg-[#2b241b]/60 rounded border border-[#d1b05a]/30 flex justify-between px-3 py-2">
          <div class="opacity-80">{{ label }}</div>
          <div class="font-medium">{{ value || '—' }}</div>
        </div>
      `,
    },
  },
}
</script>

<style scoped>
/* 画像は“原寸優先”。縦が --maxh を超える時のみ縮小 */
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
