<!-- resources/js/Pages/Schedule/Index.vue -->
<script setup>
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
  calendar4: { type: Array, default: () => [] }, // [{date,label,items:[]}]
  offset:    { type: Number, default: 0 },
  rangeLabel:{ type: String, default: '' },
})

const go = (delta) => {
  router.get(route('schedule'), { offset: props.offset + delta }, {
    preserveScroll: true,
    preserveState: true,
    replace: true,
  })
}
</script>

<template>
  <div class="min-h-dvh bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%] text-white/90">
    <div class="max-w-6xl mx-auto px-4 py-6">
      <!-- ヘッダ -->
      <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl md:text-2xl font-bold tracking-wide">スケジュール</h1>
        <Link href="/" class="text-sm underline hover:opacity-80">← ダッシュボードへ</Link>
      </div>

      <!-- ナビゲーション -->
      <div class="flex items-center justify-center gap-3 mb-6">
        <button @click="go(-4)" class="px-3 py-1 rounded bg-white/10 border border-white/20 hover:bg-white/20">« 前の4日</button>
        <div class="text-yellow-200 font-semibold">{{ props.rangeLabel }}</div>
        <button @click="go(4)" class="px-3 py-1 rounded bg-white/10 border border-white/20 hover:bg-white/20">次の4日 »</button>
        <button @click="go(-props.offset)" class="ml-3 px-3 py-1 rounded bg-white/10 border border-white/20 hover:bg-white/20">今日へ</button>
      </div>

      <!-- 4日分グリッド -->
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <div v-for="day in props.calendar4" :key="day.date"
             class="rounded-lg bg-white/10 border border-white/20 overflow-hidden">
          <div class="px-4 py-2 bg-black/30 text-yellow-200 font-semibold text-center">
            {{ day.label }}
          </div>

          <ul class="divide-y divide-white/10">
            <li v-if="!day.items.length" class="px-4 py-6 text-center text-white/60">
              予約可能です。
            </li>

            <li v-for="it in day.items" :key="it.id" class="px-4 py-3 flex items-center gap-3">
              <img v-if="it.photo" :src="it.photo" alt="" class="h-10 w-10 rounded object-cover"/>
              <div class="flex-1 min-w-0">
                <div class="font-semibold truncate">{{ it.name }}</div>
                <div class="text-xs text-white/70">{{ it.start }} - {{ it.end }}</div>
              </div>
              <span v-if="it.is_reserved"
                    class="text-[10px] px-2 py-1 rounded bg-red-500/20 text-red-300 border border-red-500/30">
                予約済
              </span>
            </li>
          </ul>
        </div>
      </div>

      <!-- フッタ -->
      <div class="mt-10 pt-6 border-t border-white/10 text-center">
        <div class="text-sm text-white/80">&copy; 2025 choco</div>
      </div>
    </div>
  </div>
</template>
