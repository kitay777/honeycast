<!-- resources/js/Pages/Cast/Show.vue -->
<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({
  cast: { type: Object, required: true },          // { id, photo_path, is_blur_default, viewer_has_unblur_access, ... }
  schedule: { type: Array, default: () => [] },
  // サーバから来ていなくても安全に動くようデフォルト用意
  unblur: { type: Object, default: () => ({ requested: false, status: null }) },
})

/** ぼかし判定（サーバから should_blur が来ていなくても防御的に算出） */
const shouldBlur = computed(() => {
  // サーバが should_blur を埋めているならそれを優先
  const supplied = props.cast?.should_blur
  if (supplied !== undefined && supplied !== null) return !!supplied

  // そうでなければ is_blur_default × !viewer_has_unblur_access で算出
  const def = props.cast?.is_blur_default
  const hasAccess = !!props.cast?.viewer_has_unblur_access
  const defaultFlag = (def === undefined || def === null) ? true : !!def // デフォルトはブラーON
  return defaultFlag && !hasAccess
})

const hasUnblurRequest = computed(() => !!props.unblur && !!props.unblur.requested)
const unblurStatus = computed(() => (props.unblur && props.unblur.status) ? props.unblur.status : null)

const requesting = ref(false)
const requestUnblur = () => {
  if (requesting.value) return
  requesting.value = true
  router.post(`/casts/${props.cast.id}/unblur-requests`, {}, {
    onFinish: () => { requesting.value = false },
  })
}
</script>

<template>
  <AppLayout>
    <div
      class="pt-4 pb-28 px-4 text-white/90 bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%]"
    >
      <!-- 顔写真 + 名前行 -->
      <section
        class="mx-auto max-w-[780px] bg-[#2b241b]/60 rounded-lg border border-[#d1b05a]/50 p-3"
      >
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="inline-block w-3 h-3 rounded-full bg-green-400"></span>
            <div class="text-xl font-semibold tracking-wide">
              {{ props.cast.nickname ?? "name" }}
            </div>
          </div>
          <img src="/assets/icons/like-badge.png" class="h-8" />
        </div>

        <!-- 写真 -->
        <div class="mt-2 relative aspect-[3/2] bg-white rounded overflow-hidden ring-1 ring-black/10">
          <img
            :src="props.cast.photo_path ? `/storage/${props.cast.photo_path}` : '/assets/imgs/placeholder.png'"
            class="w-full h-full object-cover transition will-change-transform"
            :class="shouldBlur ? 'blur-lg scale-105' : ''"
            draggable="false"
          />
          <!-- ぼかしバッジ（小さめ） -->
          <div
            v-if="shouldBlur"
            class="absolute top-2 left-2 bg-black/45 text-white text-xs px-2 py-1 rounded"
          >
            🔒 ぼかし中
          </div>
        </div>

        <!-- 星とアクション -->
        <div class="mt-2 flex items-center justify-between">
          <div class="text-[#ffcc66]">★ ★ ★ ★ ☆</div>

          <div class="flex items-center gap-3">
            <!-- ぼかし解除申請ボタン（メッセージの左） -->
            <button
              v-if="shouldBlur && !hasUnblurRequest"
              @click="requestUnblur"
              :disabled="requesting"
              class="px-4 py-2 rounded bg-[#ffe7b3] text-black shadow disabled:opacity-60 disabled:pointer-events-none"
            >
              ぼかしを外す申請
            </button>
            <span
              v-else-if="shouldBlur && hasUnblurRequest"
              class="px-4 py-2 rounded bg-[#bfb6a3] text-black/90 shadow text-sm"
              title="キャストの承認待ちです"
            >
              申請済み<span v-if="unblurStatus">（{{ unblurStatus }}）</span>
            </span>

            <button class="px-4 py-2 rounded bg-[#e7d7a0] text-black shadow">
              メッセージを送る
            </button>
            <button class="px-4 py-2 rounded bg-[#a99a86] text-black shadow">
              指名する
            </button>
          </div>
        </div>
      </section>

      <!-- スケジュール -->
      <section class="mx-auto max-w-[780px] mt-6">
        <div class="text-center text-lg bg-[#6b4b17] border border-[#d1b05a] py-1 rounded">
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
            <div class="mt-2 text-yellow-200 text-xs" v-if="d.slots?.length">
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
            <span
              v-for="t in props.cast.tags || []"
              :key="t"
              class="px-3 py-1 rounded-full bg-[#ffe09a] text-black text-xs shadow"
            >
              {{ t }}
            </span>
            <span v-if="!(props.cast.tags && props.cast.tags.length)" class="opacity-60 text-sm">—</span>
          </div>
        </div>
      </section>

      <!-- ツイート等のセクションは後で差し込み可能 -->
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
