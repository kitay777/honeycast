<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  cast: Object,
  requests: Array, // accepted なリクエスト一覧
})

const form = useForm({
  cast_profile_id: props.cast?.id || null,
  call_request_id: null,
  call_request_cast_id: null,
  duration: 60,
  latitude: null,
  longitude: null,
})

async function getLocation() {
  return new Promise((resolve) => {
    navigator.geolocation.getCurrentPosition(
      (pos) => resolve(pos.coords),
      () => resolve(null),
      { enableHighAccuracy: true, timeout: 8000 }
    )
  })
}

async function startMatch() {
  if (!form.call_request_id) {
    alert('開始するリクエストを選択してください')
    return
  }

  const coords = await getLocation()
  if (coords) {
    form.latitude = coords.latitude
    form.longitude = coords.longitude
  }

  form.post(route('matches.start'))
}
</script>

<template>
  <AppLayout>
    <div class="p-6 text-white bg-[url('/assets/imgs/back.png')] bg-cover min-h-screen">
      <h1 class="text-2xl font-bold mb-4 text-center">マッチ開始（CAST用）</h1>

      <div class="max-w-md mx-auto bg-[#2b241b]/60 border border-[#d1b05a]/40 rounded-lg p-6 shadow-md space-y-6">
        <div class="text-center text-lg">
          ようこそ <span class="text-yellow-300">{{ props.cast.nickname }}</span> さん
        </div>

        <div v-if="props.requests.length">
          <label class="block text-sm mb-1">開始するリクエストを選択</label>
<select
  v-model.number="form.call_request_cast_id"
  class="w-full rounded-md text-black px-3 py-2"
  @change="form.call_request_id = props.requests.find(r => r.id === form.call_request_cast_id)?.call_request_id"
>
  <option disabled value="">選択してください</option>
  <option
    v-for="r in props.requests"
    :key="r.id"
    :value="r.id"
  >
    {{ r.call_request?.user?.nickname || r.call_request?.user?.name || '不明なユーザー' }}
    （リクエストID: {{ r.call_request_id }}）
  </option>
</select>

          <label class="block text-sm mb-1 mt-4">マッチ時間を選択</label>
          <select v-model.number="form.duration" class="w-full rounded-md text-black px-3 py-2">
            <option value="60">1時間</option>
            <option value="120">2時間</option>
            <option value="180">3時間</option>
          </select>

          <button
            @click="startMatch"
            :disabled="form.processing"
            class="w-full bg-green-500 hover:bg-green-600 py-3 rounded-md text-lg font-semibold shadow mt-6"
          >
            🎬 マッチ開始
          </button>
        </div>
        <div v-else class="text-center text-gray-300">開始可能なリクエストはありません。</div>
      </div>
    </div>
  </AppLayout>
</template>
