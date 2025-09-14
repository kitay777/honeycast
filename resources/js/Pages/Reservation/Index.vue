<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  list: { type: Array, default: () => [] },
})
</script>

<template>
  <AppLayout>
    <div class="pt-6 pb-28 px-4 text-white/90
                bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%]">
      <h1 class="text-center text-2xl tracking-[0.6em] mb-4">予約リスト</h1>
      <div class="h-[2px] w-full bg-gradient-to-r from-[#c8a64a] via-[#e6d08a] to-[#c8a64a] mb-6"></div>

      <div v-for="(item, idx) in props.list" :key="item.id"
           class="mb-8 p-4 border border-[#d1b05a]/50 rounded-lg bg-[#2b241b]/60 shadow">
        <div class="text-xl font-bold mb-2">{{ idx + 1 }}</div>
        <div class="mb-2">
          {{ item.date_str }}　{{ item.start_time }} ～ {{ item.end_time }}
        </div>

        <div v-if="item.set_plan" class="mb-2">
          {{ item.set_plan }}
        </div>

        <div class="mb-2">
          cast {{ item.cast_count }}名　お客様 {{ item.guest_count }}名
          <span v-if="item.nomination">　{{ item.nomination }} 指名</span>
        </div>

        <div>場所　{{ item.place }}</div>
      </div>

      <div v-if="!props.list.length" class="text-center opacity-70 py-10">
        予約はまだありません。
      </div>
    </div>
  </AppLayout>
</template>
