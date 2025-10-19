<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import CastCard from '@/Components/Cast/CastCard.vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
  selected_date: { type: String, required: true },       // 'YYYY-MM-DD'
  days:          { type: Array,  default: () => [] },    // [{date,label,is_today}]
  items:         { type: Array,  default: () => [] },    // [{id, nickname, photo_path, time_text, ...}]
})

// 日付タブクリック
const goDate = (d) => {
  router.get(route('shifts.index'), { date: d }, { preserveScroll: true, replace: true })
}
</script>

<template>
  <AppLayout>
    <div class="px-4 py-4 text-white/90 bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%]">

      <!-- タブ（本日出勤 / 在籍一覧のような切替が必要ならここに追加） -->
      <div class="grid grid-cols-2 gap-2 mb-3">
        <button class="py-2 rounded border border-sky-400 bg-sky-500 text-white font-bold">本日出勤</button>
        <Link href="/roster" class="py-2 rounded border border-white/20 text-white/80 text-center">在籍一覧</Link>
      </div>

      <!-- 検索ボックス（ダミー） -->
      <div class="mb-3">
        <input placeholder="検索条件を設定する" class="w-full px-3 py-2 rounded bg-white/10 border border-white/20 placeholder-white/50"/>
      </div>

      <!-- 日付チップ（横スクロール） -->
      <div class="flex gap-2 overflow-x-auto no-scrollbar mb-4">
        <button
          v-for="d in props.days"
          :key="d.date"
          @click="goDate(d.date)"
          class="px-4 py-2 rounded-lg border text-sm whitespace-nowrap transition"
          :class="[
            d.date === props.selected_date ? 'bg-white text-black border-white' : 'bg-white/5 text-white border-white/20 hover:bg-white/10',
            d.is_today && d.date !== props.selected_date ? 'ring-1 ring-sky-400' : ''
          ]"
        >
          {{ d.label }}
        </button>
      </div>

      <!-- 時間帯ラベル（任意、ここでは選択日の最小-最大時間をざっくり表示） -->
      <div v-if="props.items.length" class="mb-2 text-right text-white/70 text-sm">
        {{ props.items[0]?.time_text ?? '' }} <!-- 実運用では集計して表示してもOK -->
      </div>

      <!-- 出勤カード -->
      <div v-if="!props.items.length" class="text-white/70 py-10 text-center">
        本日の出勤はまだ登録がありません。
      </div>

      <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-4">
        <div v-for="c in props.items" :key="c.shift_id" class="relative">
          <!-- 右上のハートアイコンが不要なら CastCard の liked を渡さない or false -->
          <CastCard :cast="c" :liked="c.liked" />
          <!-- 時間帯 -->
          <div class="mt-1 text-center text-sm font-medium">
            {{ c.time_text }}
          </div>
        </div>
      </div>

      <!-- 右下の問い合わせ FAB（任意） -->
      <Link href="/contact"
            class="fixed bottom-24 right-4 md:right-6 h-12 w-12 rounded-full bg-sky-500 shadow flex items-center justify-center">
        <span class="text-xs">問い合わせ</span>
      </Link>
    </div>
  </AppLayout>
</template>

<style>
.no-scrollbar::-webkit-scrollbar{ display:none; }
.no-scrollbar{ -ms-overflow-style:none; scrollbar-width:none; }
</style>
