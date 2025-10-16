<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
const props = defineProps({
  items: { type: Array, default: () => [] },
})
</script>

<template>
  <AppLayout>
    <div class="px-4 py-6 text-white/90
                bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%]">
      <div class="max-w-5xl mx-auto">
        <h1 class="text-2xl font-bold tracking-wide mb-4">イベント</h1>

        <div v-if="!props.items.length" class="text-white/70">現在、公開中のイベントはありません。</div>

        <ul v-else class="space-y-4">
          <li v-for="e in props.items" :key="e.id"
              class="rounded-lg bg-white/10 border border-white/20 overflow-hidden">
            <div class="flex gap-4 p-4">
              <img v-if="e.image" :src="e.image" alt="" class="h-20 w-32 object-cover rounded"/>
              <div class="flex-1">
                <div class="font-semibold text-lg">{{ e.title }}</div>
                <div class="text-xs text-white/70 mt-1">
                  <span v-if="e.is_all_day">終日</span>
                  <span v-else>{{ e.starts_at }} <span v-if="e.ends_at">〜 {{ e.ends_at }}</span></span>
                  <span v-if="e.place" class="ml-2">＠{{ e.place }}</span>
                </div>
                <p v-if="e.body" class="text-sm text-white/80 mt-2 whitespace-pre-line">
                  {{ e.body }}
                </p>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </AppLayout>
</template>
