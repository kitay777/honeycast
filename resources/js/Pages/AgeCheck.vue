<script setup>
import { router } from '@inertiajs/vue3'

// Yes ボタンを押したときの処理
const handleYes = () => {
  router.visit('/dashboard') // 認証済みトップやダッシュボードに飛ばす
}

// No ボタンを押したときの処理
const handleNo = () => {
  window.location.href = 'https://www.google.com' // 退出ページに誘導など
}
</script>

<template>
  <div class="min-h-dvh w-screen flex items-center justify-center bg-black">
    <!-- ステージ：縦長想定なら 9:16 に固定（任意） -->
    <div
      class="relative w-full h-dvh md:h-[844px] md:w-[390px] mx-auto
                 bg-no-repeat bg-center bg-[length:100%_100%] overflow-hidden"
      style="background-image: url('/assets/imgs/back.png');"
    >
      <!-- 中央コンテンツ -->
      <div class="absolute inset-0 z-10 flex flex-col items-center justify-center text-center space-y-6">
        <img src="/assets/imgs/18.png" alt="18禁止" class="w-32 h-32 mx-auto" />
        <p class="text-xl text-yellow-200">あなたは18歳以上ですか？</p>
        <div class="flex justify-center gap-6 mt-2">
          <button @click="handleYes"
            class="px-6 py-3 rounded-md bg-yellow-200 text-black font-bold shadow-md hover:scale-105 transition">
            Yes
          </button>
          <button @click="handleNo"
            class="px-6 py-3 rounded-md bg-yellow-200 text-red-600 font-bold shadow-md hover:scale-105 transition">
            No
          </button>
        </div>
        <div class="mt-6 space-y-2">
          <a href="/register" class="block text-yellow-200 underline">登録のお客様はこちら</a>
          <a href="/terms" class="block text-yellow-200 underline">利用規約</a>
          <a href="/unei" class="block text-yellow-200 underline">運営</a>
        </div>
      </div>
    </div>
  </div>
</template>

