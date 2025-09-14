<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  reservation: Object,
})
</script>

<template>
  <AppLayout>
    <div class="pt-6 pb-28 px-6 text-white text-lg
                bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%]">
      <h1 class="text-center text-2xl mb-6">予約完了</h1>

      <div class="max-w-xl mx-auto bg-white/10 rounded border border-[#d1b05a]/40 p-4">
        <div class="flex gap-3 items-center mb-3">
          <img :src="reservation.cast.photo_path ? `/storage/${reservation.cast.photo_path}` : '/assets/imgs/placeholder.png'"
               class="w-16 h-16 rounded object-cover" />
          <div>
            <div class="text-xl font-semibold">{{ reservation.cast.nickname }}</div>
            <div class="text-sm opacity-80">予約者：{{ reservation.user.name }}</div>
          </div>
        </div>

        <div class="space-y-1">
          <div>日付：{{ reservation.date }}</div>
          <div>時間：{{ reservation.start_time }} 〜 {{ reservation.end_time }}</div>
          <div>支払：{{ reservation.payment_method ?? '—' }}</div>
          <div>状態：{{ reservation.status }}</div>
          <div v-if="reservation.note">備考：{{ reservation.note }}</div>
        </div>
      </div>

      <div class="text-center mt-6">
        <Link href="/dashboard" class="underline text-yellow-200">ダッシュボードへ戻る</Link>
      </div>
    </div>
  </AppLayout>
</template>
