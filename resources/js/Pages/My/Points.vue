<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
const props = defineProps({ balance: Number, history: Array })
</script>

<template>
  <AppLayout>
    <div class="px-4 py-6 text-white/90">
      <h1 class="text-2xl font-bold mb-4">ポイント</h1>
      <div class="rounded-lg bg-white/10 border border-white/20 p-4 mb-4">
        <div class="text-sm text-white/70">現在の残高</div>
        <div class="text-4xl font-extrabold">{{ (props.balance||0).toLocaleString() }} pt</div>
      </div>

      <div class="rounded-lg bg-white/10 border border-white/20 p-4">
        <div class="font-semibold mb-2">履歴（最新20件）</div>
        <div v-if="!props.history?.length" class="text-white/60 text-sm">履歴はまだありません。</div>
        <ul v-else class="divide-y divide-white/10">
          <li v-for="h in props.history" :key="h.id" class="py-2 flex items-center justify-between">
            <div class="text-sm" :class="h.delta >= 0 ? 'text-emerald-300' : 'text-rose-300'">
              {{ h.delta >= 0 ? '+' : '' }}{{ h.delta }} pt
              <div class="text-white/60 text-xs">{{ h.reason || '—' }}</div>
            </div>
            <div class="text-right text-xs text-white/60">
              <div>{{ h.created_at }}</div>
              <div>残高: {{ h.after }}</div>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </AppLayout>
</template>
