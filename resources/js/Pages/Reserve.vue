<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  casts: { type: Array, default: () => [] }, // [{id,nickname,photo_path}]
})

const form = useForm({
  cast_profile_id: '',
  date: '',
  start_time: '20:00',
  end_time:   '22:00',
  payment_method: 'card',
  note: '',
})

const submit = () => form.post(route('reserve.store'))
</script>

<template>
  <AppLayout>
    <div class="pt-6 pb-28 px-6 text-white text-lg
                bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%]">
      <h1 class="text-center text-2xl mb-6">予約</h1>

      <form @submit.prevent="submit" class="max-w-xl mx-auto space-y-5">
        <div>
          <label class="block text-sm mb-1">キャスト</label>
          <select v-model="form.cast_profile_id" class="w-full h-11 rounded px-3 text-black">
            <option value="" disabled>選択してください</option>
            <option v-for="c in props.casts" :key="c.id" :value="c.id">{{ c.nickname }}</option>
          </select>
          <p v-if="form.errors.cast_profile_id" class="text-red-300 text-sm mt-1">{{ form.errors.cast_profile_id }}</p>
        </div>

        <div>
          <label class="block text-sm mb-1">日付</label>
          <input v-model="form.date" type="date" class="w-full h-11 rounded px-3 text-black" />
          <p v-if="form.errors.date" class="text-red-300 text-sm mt-1">{{ form.errors.date }}</p>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm mb-1">開始</label>
            <input v-model="form.start_time" type="time" class="w-full h-11 rounded px-3 text-black" />
          </div>
          <div>
            <label class="block text-sm mb-1">終了</label>
            <input v-model="form.end_time" type="time" class="w-full h-11 rounded px-3 text-black" />
          </div>
        </div>
        <p v-if="form.errors.start_time" class="text-red-300 text-sm">{{ form.errors.start_time }}</p>
        <p v-if="form.errors.end_time" class="text-red-300 text-sm">{{ form.errors.end_time }}</p>

        <div>
          <label class="block text-sm mb-1">支払い方法</label>
          <select v-model="form.payment_method" class="w-full h-11 rounded px-3 text-black">
            <option value="card">クレジットカード</option>
            <option value="cash">現金</option>
          </select>
        </div>

        <div>
          <label class="block text-sm mb-1">備考</label>
          <textarea v-model="form.note" rows="4" class="w-full rounded px-3 py-2 text-black" placeholder="要望など"></textarea>
        </div>

        <div class="pt-2">
          <button :disabled="form.processing"
                  class="w-full h-12 rounded-full text-xl bg-gradient-to-r from-[#caa14b] to-[#f0e1b1]
                         text-black font-bold tracking-[0.5em] shadow">
            予 約 す る
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
