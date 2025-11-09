<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({ castId: Number })

const form = useForm({
  cast_profile_id: props.castId,
  duration: 60,
  latitude: null,
  longitude: null,
})

const started = ref(false)
const matchId = ref(null)

async function getLocation() {
  return new Promise((resolve) => {
    navigator.geolocation.getCurrentPosition(
      (pos) => resolve(pos.coords),
      () => resolve(null),
      { enableHighAccuracy: true, timeout: 5000 }
    )
  })
}

async function startMatch() {
  const coords = await getLocation()
  if (coords) {
    form.latitude = coords.latitude
    form.longitude = coords.longitude
  }
  form.post(route('matches.start'), {
    onSuccess: (res) => {
      started.value = true
      matchId.value = res?.props?.match_id || null
      alert('マッチを開始しました！')
    },
  })
}

async function endMatch() {
  if (!matchId.value) return alert('マッチ情報がありません')
  await fetch(`/matches/${matchId.value}/end`, {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
  })
  alert('マッチを終了しました')
  started.value = false
}
</script>

<template>
  <AppLayout>
    <div class="p-6 text-white">
      <h1 class="text-2xl mb-4">マッチ管理</h1>

      <div v-if="!started" class="space-y-4">
        <label>マッチ時間を選択</label>
        <select v-model.number="form.duration" class="text-black rounded px-2 py-1">
          <option value="60">1時間</option>
          <option value="120">2時間</option>
          <option value="180">3時間</option>
        </select>
        <button @click="startMatch" class="bg-green-500 px-4 py-2 rounded">マッチ開始</button>
      </div>

      <div v-else>
        <p>マッチ中（{{ form.duration }}分）</p>
        <button @click="endMatch" class="bg-red-600 px-4 py-2 rounded mt-4">マッチ終了</button>
      </div>
    </div>
  </AppLayout>
</template>
