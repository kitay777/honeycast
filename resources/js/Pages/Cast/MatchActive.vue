<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  match: Object,
})

async function endMatch() {
  await fetch(`/matches/${props.match.id}/end`, {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
  })
  alert('🏁 マッチを終了しました')
  location.href = '/cast/match'
}

async function extendMatch(hours) {
  await fetch(`/matches/${props.match.id}/extend`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
    },
    body: JSON.stringify({ hours }),
  })
  alert(`⏱ ${hours}時間延長しました`)
}
</script>

<template>
  <AppLayout>
    <div class="p-6 text-white bg-[url('/assets/imgs/back.png')] bg-cover min-h-screen text-center">
      <h1 class="text-3xl font-bold mb-6">マッチ中</h1>
      <p>開始時刻：{{ new Date(match.started_at).toLocaleString() }}</p>
      <p>所要時間：{{ match.duration_minutes / 60 }}時間</p>
      <p v-if="match.latitude">位置情報：{{ match.latitude }}, {{ match.longitude }}</p>

      <div class="mt-8 space-x-4">
        <button @click="endMatch" class="bg-red-600 text-white px-6 py-3 rounded-lg">
          🏁 マッチ終了
        </button>
        <button @click="extendMatch(1)" class="bg-blue-500 text-white px-4 py-2 rounded-lg">
          ＋1時間延長
        </button>
        <button @click="extendMatch(2)" class="bg-blue-500 text-white px-4 py-2 rounded-lg">
          ＋2時間延長
        </button>
      </div>
    </div>
  </AppLayout>
</template>
