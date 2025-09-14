<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  cast:   { type: Object, required: true },
  liked:  { type: Boolean, default: false },
  online: { type: Boolean, default: false },
  rating: { type: Number,  default: 3.5 },
})

/**
 * サーバから should_blur が来ていればそれを採用。
 * 来ていない（undefined/null）場合は is_blur_default と viewer_has_unblur_access から自前で算出。
 * 既定は「ブラー ON」寄りに倒す。
 */
const shouldBlur = computed(() => {
  const supplied = props.cast?.should_blur
  if (supplied !== undefined && supplied !== null) return !!supplied

  const def = props.cast?.is_blur_default
  const hasAccess = !!props.cast?.viewer_has_unblur_access
  // 既定が不明(null/undefined)なら true 扱い → デフォルトはブラー
  const defaultFlag = (def === undefined || def === null) ? true : !!def
  return defaultFlag && !hasAccess
})
</script>

<template>
  <Link :href="`/casts/${cast.id}`" class="block">
    <div class="relative rounded-lg p-2 bg-gradient-to-b from-[#ffebc9] to-[#caa14b] shadow">
      <div class="rounded-md bg-white p-2">
        <div class="relative aspect-[3/4] overflow-hidden rounded-sm">
          <img
            :src="cast.photo_path ? `/storage/${cast.photo_path}` : '/assets/imgs/placeholder.png'"
            class="w-full h-full object-cover transition will-change-transform"
            :class="shouldBlur ? 'blur-lg scale-105' : ''"
            draggable="false"
          />
          <img v-if="liked" src="/assets/icons/like-badge.png" class="absolute top-1 right-1 h-8" />
          <div v-if="shouldBlur" class="absolute inset-0 flex items-center justify-center">
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
  </Link>
</template>
