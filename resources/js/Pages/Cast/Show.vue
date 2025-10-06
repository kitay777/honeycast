<!-- resources/js/Pages/Cast/Show.vue -->
<script setup>
import { computed, ref, watch } from "vue"
import { router, Link } from "@inertiajs/vue3"
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
  // 期待する shape:
  // cast.photos: [{ id, url, sort_order, is_primary:boolean, should_blur:boolean, unblur?: {granted?:bool,status?:'approved'|'pending'} }]
  // cast.viewer_has_unblur_access?: boolean
  cast: { type: Object, required: true },
  schedule: { type: Array, default: () => [] },
  unblur: { type: Object, default: () => ({ requested:false, status:null }) },
})

/* ====== 写真 ====== */
const gallery = computed(() => Array.isArray(props.cast?.photos) ? props.cast.photos : [])

/** 後方互換: photo_path を URL に変換して一致比較用 */
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

/** props 更新に追従（保存直後の参照ズレ防止） */
watch(gallery, (photos) => {
  const arr = photos ?? []
  if (!current.value) {
    current.value = pickCurrent(arr)
    return
  }
  const updated = arr.find(p => p.id === current.value.id)
  current.value = updated ?? pickCurrent(arr)
})

/* ====== ぼかし判定（要件: 初期は全て非ぼかし / 写真にフラグがあり未許可の時だけぼかす / primary は常に非ぼかし） ====== */
const hasProfileAccess = computed(() => !!props.cast?.viewer_has_unblur_access) // プロフィール全体の許可

// 写真単位で閲覧可能か（プロフィール全体許可 or 個別許可）
const photoAllowed = (p) => {
  const u = p?.unblur ?? {}
  return hasProfileAccess.value || u.granted === true || u.status === 'approved'
}

// その写真をぼかすべきか
const photoShouldBlur = (p) => p?.should_blur === true && !photoAllowed(p)

// メイン表示のぼかし（primary は常にオフ）
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

/* ====== チャット開始 ====== */
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
        <div class="mt-2 relative aspect-[3/2] bg-white rounded overflow-hidden ring-1 ring-black/10">
          <img
            :src="current
                    ? current.url
                    : (props.cast.photo_path ? `/storage/${props.cast.photo_path}` : '/assets/imgs/placeholder.png')"
            class="w-full h-full object-cover transition will-change-transform"
            :class="shouldBlur ? 'blur-lg scale-105' : ''"
            draggable="false"
            alt="main"
          />
          <div v-if="shouldBlur" class="absolute top-2 left-2 bg-black/45 text-white text-xs px-2 py-1 rounded">🔒 ぼかし中</div>
        </div>

        <!-- サムネ（横スクロール） -->
        <div v-if="gallery.length" class="mt-3 relative">
          <div class="flex gap-3 overflow-x-auto no-scrollbar -mx-2 px-2 py-1">
            <div
              v-for="p in gallery" :key="p.id"
              class="shrink-0 w-28 h-20 rounded overflow-hidden ring-1 ring-black/20 relative cursor-pointer"
              @click="current = p" role="button" tabindex="0"
            >
              <img :src="p.url" class="w-full h-full object-cover transition"
                   :class="photoShouldBlur(p) ? 'blur-md scale-[1.03]' : ''" />

              <!-- 個別申請ボタン（ぼかし中・未申請の時だけ） -->
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
            <!-- プロファイル単位のぼかし解除申請を使う場合は有効化
            <button
              v-if="!hasProfileAccess && !hasUnblurRequest"
              @click="requestUnblurProfile"
              :disabled="requesting"
              class="px-4 py-2 rounded bg-[#ffe7b3] text-black shadow disabled:opacity-60 disabled:pointer-events-none">
              ぼかしを外す申請
            </button>
            <span v-else-if="!hasProfileAccess && hasUnblurRequest"
                  class="px-4 py-2 rounded bg-[#bfb6a3] text-black/90 shadow text-sm">
              申請済み<span v-if="unblurStatus">（{{ unblurStatus }}）</span>
            </span>
            -->


<Link
  as="button"
  method="post"
  :href="urlFor('casts.startChat', props.cast.id, `/casts/${props.cast.id}/start-chat`)"
  class="px-4 py-2 rounded bg-[#e7d7a0] text-black shadow"
>
  ギフトを贈る
</Link>
<!--
            <button class="px-4 py-2 rounded bg-[#a99a86] text-black shadow">指名する</button>
-->
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
    <!-- 固定CTA: フッターの上に常に表示 -->
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
