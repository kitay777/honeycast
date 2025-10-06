<script setup>
import { Link } from '@inertiajs/vue3'
</script>

<template>
  <div class="min-h-dvh bg-black text-white">
    <!-- ヘッダー固定 -->
    <header
      class="fixed top-0 left-0 right-0 z-50 bg-[#4b3621] text-yellow-200
             flex justify-around py-3 shadow h-16"
    >
      <Link href="/mypage" class="flex flex-col items-center">
        <img src="/assets/icons/mypage.png" class="h-12 mb-1" />
      </Link>
      <Link href="/ranking" class="flex flex-col items-center">
        <img src="/assets/icons/rank.png" class="h-12 mb-1" />
      </Link>
      <Link href="/system" class="flex flex-col items-center">
        <img src="/assets/icons/system.png" class="h-12 mb-1" />
      </Link>
      <Link href="/likes" class="flex flex-col items-center">
        <img src="/assets/icons/like.png" class="h-12 mb-1" />
      </Link>
      <Link href="/contact" class="flex flex-col items-center">
        <img src="/assets/icons/contact.png" class="h-12 mb-1" />
      </Link>
      <Link href="/dashboard" class="flex flex-col items-center">
        <img src="/assets/icons/home.png" class="h-12 mb-1" />
      </Link>
    </header>

    <!-- コンテンツ部分 -->
    <main class="pt-16 pb-20 overflow-y-auto">
      <slot />
    </main>

    <!-- フッター固定 -->
    <footer
      class="fixed bottom-0 left-0 right-0 z-50 bg-[#4b3621] text-yellow-200
             grid grid-cols-5 items-center h-20 shadow"
    >
      <Link href="/search"  class="justify-self-center"><img src="/assets/icons/search.png"  class="h-12" /></Link>
      <Link href="/tweets"   class="justify-self-center"><img src="/assets/icons/tweet.png"   class="h-12" /></Link>

      <!-- 中央セル -->
<div class="relative justify-self-center -mt-8">
  <Link href="/call" class="block w-24 aspect-square">  <!-- ← 幅を基準に正方形化 -->
    <img src="/assets/icons/call.png" class="w-full h-full object-contain" />
  </Link>
</div>

      <Link href="/reservations" class="justify-self-center"><img src="/assets/icons/reserve.png" class="h-12" /></Link>
      <Link href="/chat" class="justify-self-center"><img src="/assets/icons/message.png" class="h-12" /></Link>
    </footer>
  </div>
</template>
