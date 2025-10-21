<!-- resources/js/Pages/Cast/Qr.vue -->
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  registerUrl: String,
  qrBase64: String,
})
</script>

<template>
  <AppLayout>
    <div class="flex flex-col items-center justify-center min-h-[80vh] text-white">
      <h1 class="text-2xl font-bold mb-6">キャスト登録QRコード</h1>

      <img :src="props.qrBase64" alt="Cast Register QR" class="w-64 h-64 bg-white p-3 rounded-xl shadow" />

      <p class="mt-6 text-center text-gray-300">
        スマートフォンでQRコードを読み取ると<br />
        キャスト登録ページ（<Link :href="props.registerUrl" class="underline text-yellow-400">こちら</Link>）へアクセスできます。
      </p>
    </div>
  </AppLayout>
</template>
