<!-- resources/js/Pages/Ranking.vue -->
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import CastCard  from '@/Components/Cast/CastCard.vue'

const props = defineProps({
  nominations: { type: Array, default: () => [] }, // 本指名
  access:      { type: Array, default: () => [] }, // アクセス
  newbies:     { type: Array, default: () => [] }, // 新人
})

// 王冠（public/assets/icons に配置）
const crowns = [
  { img: '/assets/icons/crown-gold.png',   label: 'No1' },
  { img: '/assets/icons/crown-silver.png', label: 'No2' },
  { img: '/assets/icons/crown-bronze.png', label: 'No3' },
]
</script>

<template>
  <AppLayout>
    <div class="pt-6 pb-28 px-4 text-white/90
                bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%]">

      <!-- 見出し -->
      <h1 class="text-center text-2xl tracking-[0.6em] mb-3">ランキング</h1>
      <div class="h-[2px] w-full bg-gradient-to-r from-[#c8a64a] via-[#e6d08a] to-[#c8a64a] mb-6"></div>

      <!-- ==== 本指名ランキング ==== -->
      <div class="mb-8">
        <div class="inline-block px-4 py-1 rounded-lg bg-[#6b4b17] border border-[#d1b05a] text-[18px] tracking-[0.3em] shadow-sm mb-3">
          本指名ランキング
        </div>

        <div class="overflow-x-auto snap-x snap-mandatory [-webkit-overflow-scrolling:touch]">
          <div class="grid grid-flow-col auto-cols-[minmax(180px,1fr)] gap-4 min-w-[600px]">
            <div v-for="(c, idx) in props.nominations" :key="c.id" class="relative snap-start">
              <div v-if="idx < 3" class="absolute -top-3 -left-3 z-20 flex items-center gap-1">
                <img :src="crowns[idx].img" class="h-24 drop-shadow" />
                <span class="text-xs font-bold text-yellow-200">{{ crowns[idx].label }}</span>
              </div>
              <CastCard :cast="c" :liked="idx===0 || idx===2" />
            </div>
          </div>
        </div>
      </div>

      <!-- ==== アクセスランキング ==== -->
      <div class="mb-8">
        <div class="inline-block px-4 py-1 rounded-lg bg-[#6b4b17] border border-[#d1b05a] text-[18px] tracking-[0.3em] shadow-sm mb-3">
          アクセスランキング
        </div>

        <div class="overflow-x-auto snap-x snap-mandatory [-webkit-overflow-scrolling:touch]">
          <div class="grid grid-flow-col auto-cols-[minmax(180px,1fr)] gap-4 min-w-[600px]">
            <div v-for="(c, idx) in props.access" :key="c.id" class="relative snap-start">
              <div v-if="idx < 3" class="absolute -top-3 -left-3 z-20 flex items-center gap-1">
                <img :src="crowns[idx].img" class="h-24 drop-shadow" />
                <span class="text-xs font-bold text-yellow-200">{{ crowns[idx].label }}</span>
              </div>
              <CastCard :cast="c" :liked="idx===0 || idx===2" />
            </div>
          </div>
        </div>
      </div>

      <!-- ==== 新人ランキング ==== -->
      <div class="mb-8">
        <div class="inline-block px-4 py-1 rounded-lg bg-[#6b4b17] border border-[#d1b05a] text-[18px] tracking-[0.3em] shadow-sm mb-3">
          新人ランキング
        </div>

        <div class="overflow-x-auto snap-x snap-mandatory [-webkit-overflow-scrolling:touch]">
          <div class="grid grid-flow-col auto-cols-[minmax(180px,1fr)] gap-4 min-w-[600px]">
            <div v-for="(c, idx) in props.newbies" :key="c.id" class="relative snap-start">
              <div v-if="idx < 3" class="absolute -top-3 -left-3 z-20 flex items-center gap-1">
                <img :src="crowns[idx].img" class="h-24 drop-shadow" />
                <span class="text-xs font-bold text-yellow-200">{{ crowns[idx].label }}</span>
              </div>
              <CastCard :cast="c" :liked="idx===0 || idx===2" />
            </div>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>
