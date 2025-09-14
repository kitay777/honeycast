<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({ call: Object })
</script>

<template>
  <AppLayout>
    <div class="pt-6 pb-28 px-6 text-white/90
                bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%]">
      <h1 class="text-center text-2xl mb-6">呼び出しリクエスト送信完了</h1>

      <div class="max-w-xl mx-auto bg-white/10 rounded border border-[#d1b05a]/40 p-4 space-y-2">
        <div>場所：{{ props.call.place }}</div>
        <div>cast人数：{{ props.call.cast_count }} 名</div>
        <div>お客様人数：{{ props.call.guest_count }} 名</div>
        <div>指名：{{ props.call.nomination || '—' }}</div>
        <div>日：{{ props.call.date }}</div>
        <div>時間：{{ props.call.start_time }} 〜 {{ props.call.end_time }}</div>
        <div>set：{{ props.call.set_plan || '—' }}</div>
        <div>ゲームオプション：{{ props.call.game_option || '—' }}</div>
        <div>備考：{{ props.call.note || '—' }}</div>
        <div>状態：{{ props.call.status }}</div>
      </div>

      <div class="text-center mt-6">
        <Link href="/dashboard" class="underline text-yellow-200">ダッシュボードへ戻る</Link>
      </div>
    </div>
  </AppLayout>
</template>
