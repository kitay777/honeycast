<script setup>
import { computed, ref, watch, onMounted } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'

/**
 * Props:
 * - cast: { id, nickname, photo_path, last_login_at?, should_blur?, is_blur_default?, viewer_has_unblur_access? }
 * - liked: 初期いいね状態
 */
const props = defineProps({
  cast:   { type: Object, required: true },
  liked:  { type: Boolean, default: false },
})

onMounted(() => {
  console.log("last_login_at:", props.cast.last_login_at)
})

/** イベント定義 */
const emit = defineEmits(['update:liked'])

/** Inertia ページ情報（ログイン中ユーザーなど） */
const page = usePage()
const user = computed(() => page.props?.auth?.user || null)

/** いいねローカル状態 */
const localLiked = ref(!!props.liked)
watch(() => props.liked, (v) => (localLiked.value = !!v))

/** 二重送信防止 */
const posting = ref(false)

/** ルート生成 */
const urlFor = (name, id) => {
  if (name === 'casts.like') return `/casts/${id}/like`
  if (name === 'casts.unlike') return `/casts/${id}/like`
  return '#'
}

/** いいねトグル */
const toggleLike = () => {
  if (!user.value) {
    router.visit('/login')
    return
  }
  if (posting.value) return
  posting.value = true

  const next = !localLiked.value
  localLiked.value = next
  emit('update:liked', next)

  const href = urlFor(next ? 'casts.like' : 'casts.unlike', props.cast.id)
  const opts = {
    preserveScroll: true,
    onFinish: () => (posting.value = false),
    onError: () => {
      localLiked.value = !next
      emit('update:liked', !next)
      posting.value = false
    },
  }

  next ? router.post(href, {}, opts) : router.delete(href, opts)
}

/** ブラー判定 */
const shouldBlur = computed(() => {
  const supplied = props.cast.should_blur
  if (supplied !== undefined && supplied !== null) return !!supplied
  const def = props.cast.is_blur_default
  const hasAccess = !!props.cast.viewer_has_unblur_access
  const defaultFlag = def === undefined || def === null ? true : !!def
  return defaultFlag && !hasAccess
})

/** 画像URL */
const photoUrl = computed(() =>
  props.cast.photo_path
    ? `/storage/${props.cast.photo_path}`
    : '/assets/imgs/placeholder.png'
)

/** ✅ オンライン判定（最終ログインから15分以内） */
const isOnline = computed(() => {
  if (!props.cast?.last_login_at) return false
  const lastLogin = new Date(props.cast.last_login_at)
  const diffMinutes = (Date.now() - lastLogin.getTime()) / (1000 * 60)
  return diffMinutes <= 15
})
</script>

<template>
  <div class="block">
    <div
      class="relative rounded-lg p-2 bg-gradient-to-b from-[#ffebc9] to-[#caa14b] shadow transition-transform duration-150 hover:-translate-y-1"
    >
      <div class="rounded-md bg-white p-2">
        <!-- メイン画像 -->
        <div class="relative aspect-[3/4] overflow-hidden rounded-sm">
          <Link :href="`/casts/${cast.id}`" class="absolute inset-0 z-10" />

          <img
            :src="photoUrl"
            alt=""
            class="w-full h-full object-cover transition-transform duration-300"
            :class="shouldBlur ? 'blur-lg scale-105' : 'scale-100'"
            draggable="false"
          />

          <!-- ❤️ いいねボタン -->
          <button
            type="button"
            @click.stop.prevent="toggleLike"
            :disabled="posting"
            class="absolute top-1 right-1 h-9 w-9 rounded-full flex items-center justify-center
                   border border-white/30 shadow bg-black/40 hover:bg-black/60 transition z-20"
            :aria-pressed="localLiked"
          >
            <svg viewBox="0 0 24 24" class="h-5 w-5"
                 :fill="localLiked ? 'currentColor' : 'none'"
                 :class="localLiked ? 'text-pink-400' : 'text-white'">
              <path stroke="currentColor" stroke-width="1.6"
                    d="M12.1 20.3 4.9 13.1a5 5 0 0 1 7.1-7.1l.1.1.1-.1a5 5 0 0 1 7.1 7.1l-7.2 7.2Z"/>
            </svg>
          </button>

          <!-- 🔒 ブラー中案内 -->
          <div
            v-if="shouldBlur"
            class="absolute inset-0 flex items-center justify-center pointer-events-none"
          >
            <div
              class="backdrop-blur-sm bg-black/40 text-white px-3 py-1 rounded-full text-xs font-medium"
            >
              🔒 ぼかし中
            </div>
          </div>
        </div>

        <!-- 名前 -->
        <div
          class="mt-2 bg-[#b4882a] text-white rounded px-2 py-1 text-center font-semibold truncate"
        >
          {{ cast.nickname ?? 'name' }}
        </div>

        <!-- 🟢 オンライン / オフライン -->
        <div
          class="mt-1 rounded-full bg-[#f7f4ee] px-3 py-1 text-center text-xs text-black/70 relative"
        >
          <span
            class="absolute left-2 top-1/2 -translate-y-1/2 inline-block w-3 h-3 rounded-full"
            :class="isOnline ? 'bg-green-400' : 'bg-gray-400'"
          ></span>
          {{ isOnline ? 'オンライン（15分以内）' : 'オフライン' }}
        </div>
      </div>
    </div>
  </div>
</template>
