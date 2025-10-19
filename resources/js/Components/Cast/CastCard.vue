<script setup>
import { computed, ref, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'

/**
 * Props
 * - cast: { id, nickname, photo_path, should_blur?, is_blur_default?, viewer_has_unblur_access? }
 * - liked/online/rating は親から初期状態を受け取り、内部でトグルして即時反映
 */
const props = defineProps({
  cast:   { type: Object, required: true },
  liked:  { type: Boolean, default: false },
  online: { type: Boolean, default: false },
  rating: { type: Number,  default: 3.5 },
})

/** 親へ通知したい場合のイベント（任意）：使わなくてもOK */
const emit = defineEmits(['update:liked'])

/** Inertia ページ情報（未ログイン判定など） */
const page = usePage()
const user = computed(() => page.props?.auth?.user || null)

/** いいねのローカル状態（即時反映用） */
const localLiked = ref(!!props.liked)
watch(() => props.liked, v => (localLiked.value = !!v))

/** 二重送信防止 */
const posting = ref(false)

/** Ziggy の route() が無い環境のフォールバック（必要ならベースURLは調整） */
const urlFor = (name, id) => {
  try { if (typeof route === 'function') return route(name, id) } catch {}
  if (name === 'casts.like')   return `/casts/${id}/like`
  if (name === 'casts.unlike') return `/casts/${id}/like`
  return '#'
}

/** いいねトグル */
const toggleLike = () => {
  if (!user.value) {
    router.visit('/login') // 未ログインはログインへ
    return
  }
  if (posting.value) return
  posting.value = true

  const next = !localLiked.value
  localLiked.value = next                           // 楽観更新
  emit('update:liked', next)

  const href   = urlFor(next ? 'casts.like' : 'casts.unlike', props.cast.id)
  const baseOpts = {
    preserveScroll: true,
    onFinish: () => { posting.value = false },
    onError:  () => { localLiked.value = !next; emit('update:liked', !next); posting.value = false },
  }
  if (next) {
    // いいね（POST は data を渡すシグネチャ）
    router.post(href, {}, baseOpts)
  } else {
    // 解除（DELETE は options だけ）
    router.delete(href, baseOpts)
  }
}

/** ブラー判定（あなたのロジックを踏襲） */
const shouldBlur = computed(() => {
  const supplied = props.cast?.should_blur
  if (supplied !== undefined && supplied !== null) return !!supplied
  const def = props.cast?.is_blur_default
  const hasAccess = !!props.cast?.viewer_has_unblur_access
  const defaultFlag = (def === undefined || def === null) ? true : !!def
  return defaultFlag && !hasAccess
})

/** 画像URLの解決（storage:link 前提） */
const photoUrl = computed(() =>
  props.cast?.photo_path ? `/storage/${props.cast.photo_path}` : '/assets/imgs/placeholder.png'
)
</script>

<template>
  <!-- 画像クリックで詳細 / 右上ハートで「いいね」 -->
  <div class="block">
    <div class="relative rounded-lg p-2 bg-gradient-to-b from-[#ffebc9] to-[#caa14b] shadow">
      <div class="rounded-md bg-white p-2">
        <div class="relative aspect-[3/4] overflow-hidden rounded-sm">
          <!-- 遷移リンクは画像全体を包む -->
          <Link :href="`/casts/${cast.id}`" class="absolute inset-0 z-10" aria-label="詳細を見る" />

          <img
            :src="photoUrl"
            alt=""
            class="w-full h-full object-cover transition will-change-transform"
            :class="shouldBlur ? 'blur-lg scale-105' : ''"
            draggable="false"
          />

          <!-- いいねボタン（カード遷移を止める） -->
          <button
            type="button"
            @click.stop.prevent="toggleLike"
            :disabled="posting"
            class="absolute top-1 right-1 h-9 w-9 rounded-full flex items-center justify-center
                   border border-white/30 shadow bg-black/40 hover:bg-black/60 transition  z-20"
            :aria-pressed="localLiked"
            title="いいね"
          >
            <svg viewBox="0 0 24 24" class="h-5 w-5"
                 :fill="localLiked ? 'currentColor' : 'none'"
                 :class="localLiked ? 'text-pink-400' : 'text-white'">
              <path stroke="currentColor" stroke-width="1.6"
                    d="M12.1 20.3 4.9 13.1a5 5 0 0 1 7.1-7.1l.1.1.1-.1a5 5 0 0 1 7.1 7.1l-7.2 7.2Z"/>
            </svg>
          </button>

          <!-- 「いいね」バッジ（任意） -->
          <img v-if="localLiked" src="/assets/icons/like-badge.png" class="absolute top-1 left-1 h-8" />

          <!-- ブラー中の案内 -->
          <div v-if="shouldBlur" class="absolute inset-0 flex items-center justify-center pointer-events-none">
            <div class="backdrop-blur-sm bg-black/30 text-white px-3 py-1 rounded-full text-sm">
              🔒 ぼかし中（タップで詳細）
            </div>
          </div>
        </div>

        <div class="mt-2 bg-[#b4882a] text-white rounded px-2 py-1 flex items-center justify-between">
          <div class="text-[#ffcc66] text-sm">
            <span v-for="i in 5" :key="i">{{ i <= Math.round(rating) ? '★' : '☆' }}</span>
          </div>
          <div class="text-lg font-semibold truncate ml-2">{{ cast.nickname ?? 'name' }}</div>
        </div>

        <div class="mt-1 rounded-full bg-[#f7f4ee] px-3 py-1 text-center text-xs text-black/70 relative">
          <span class="absolute left-2 top-1/2 -translate-y-1/2 inline-block w-3 h-3 rounded-full"
                :class="online ? 'bg-green-400' : 'bg-red-400'"></span>
          コメント
        </div>
      </div>
    </div>
  </div>
</template>
