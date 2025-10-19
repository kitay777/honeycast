<script setup>
import { Link, usePage } from "@inertiajs/vue3"
import { ref, onMounted, onBeforeUnmount } from "vue"

const page = usePage()
const isMenuOpen = ref(false)

const openMenu  = () => { isMenuOpen.value = true;  document.documentElement.classList.add("overflow-hidden") }
const closeMenu = () => { isMenuOpen.value = false; document.documentElement.classList.remove("overflow-hidden") }

// ESCで閉じる
const onKeydown = (e) => {
  if (e.key === "Escape") closeMenu()
}

onMounted(() => {
  window.addEventListener("keydown", onKeydown)
})
onBeforeUnmount(() => {
  window.removeEventListener("keydown", onKeydown)
  document.documentElement.classList.remove("overflow-hidden")
})
</script>

<template>
  <div class="min-h-dvh bg-black text-white">
    <!-- ヘッダー固定 -->
    <header
      class="fixed top-0 left-0 right-0 z-50 bg-[#4b3621] text-yellow-200
             flex justify-around py-3 shadow h-16"
    >
      <Link href="/dashboard" class="flex flex-col items-center">
        <img
          :src="page.url === '/dashboard'
            ? '/assets/icons/topon.png'
            : '/assets/icons/top.png'"
          class="h-12 mb-1"
          alt="Top"
        />
      </Link>

      <Link href="/roster" class="flex flex-col items-center">
        <img
          :src="page.url === '/roster'
            ? '/assets/icons/caston.png'
            : '/assets/icons/cast.png'"
          class="h-12 mb-1"
          alt="Roster"
        />
      </Link>

      <Link href="/system" class="flex flex-col items-center">
        <img
          :src="page.url === '/system'
            ? '/assets/icons/systemon.png'
            : '/assets/icons/system.png'"
          class="h-12 mb-1"
          alt="System"
        />
      </Link>

      <Link href="/shifts" class="inline-flex items-center gap-2 px-3 py-2 rounded-md bg-white/10 border border-white/20 hover:bg-white/20">

        <img
          :src="page.url === '/likes'
            ? '/assets/icons/schon.png'
            : '/assets/icons/sch.png'"
          class="h-12 mb-1"
          alt="Likes"
        />
      </Link>

      <Link href="/contact" class="flex flex-col items-center">
        <img
          :src="page.url === '/contact'
            ? '/assets/icons/contacton.png'
            : '/assets/icons/contact.png'"
          class="h-12 mb-1"
          alt="Contact"
        />
      </Link>

      <!-- メニューボタン（押すと右からドロワー） -->
      <button
        type="button"
        @click="openMenu"
        class="flex flex-col items-center focus:outline-none"
        aria-label="Open menu"
      >
        <img src="/assets/icons/menu.png" class="h-12 mb-1" alt="Menu" />
      </button>
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
      <Link href="/search"  class="justify-self-center"><img src="/assets/icons/search.png"  class="h-12" alt="Search" /></Link>
      <Link href="/tweets"  class="justify-self-center"><img src="/assets/icons/tweet.png"   class="h-12" alt="Tweets" /></Link>

      <!-- 中央セル（丸いボタン） -->
      <div class="relative justify-self-center -mt-8">
        <Link href="/call" class="block w-24 aspect-square">
          <img src="/assets/icons/call.png" class="w-full h-full object-contain" alt="Call" />
        </Link>
      </div>

      <Link href="/reservations" class="justify-self-center"><img src="/assets/icons/reserve.png" class="h-12" alt="Reserve" /></Link>
      <Link href="/chat" class="justify-self-center"><img src="/assets/icons/message.png" class="h-12" alt="Message" /></Link>
    </footer>

    <!-- ===== ドロワー（右からスワイプイン） ===== -->
    <!-- オーバーレイ -->
    <div
      v-show="isMenuOpen"
      @click="closeMenu"
      class="fixed inset-0 z-[70] bg-black/50 backdrop-blur-[2px] transition-opacity"
    ></div>

    <!-- パネル本体 -->
    <aside
      class="fixed top-0 right-0 z-[80] h-dvh w-80 max-w-[85vw]
             bg-[#1b1b1b] text-white shadow-2xl
             transition-transform duration-300 ease-out
             overflow-y-auto"
      :class="isMenuOpen ? 'translate-x-0' : 'translate-x-full'"
      @click.stop
    >
      <!-- ヘッダー -->
      <div class="flex items-center justify-between h-16 px-4 border-b border-white/10">
        <h2 class="text-lg font-semibold">メニュー</h2>
        <button
          type="button"
          @click="closeMenu"
          class="p-2 rounded hover:bg-white/10 focus:outline-none"
          aria-label="Close menu"
        >
          ✕
        </button>
      </div>

      <!-- メニュー項目 -->
      <nav class="py-2">
        <Link
          href="/dashboard"
          class="block px-5 py-4 hover:bg-white/10"
          @click="closeMenu"
        >TOP</Link>
        <Link
          href="/system"
          class="block px-5 py-4 hover:bg-white/10"
          @click="closeMenu"
        >SYSTEM</Link>
        <Link
          href="/tweets"
          class="block px-5 py-4 hover:bg-white/10"
          @click="closeMenu"
        >ツイート</Link>
        <Link
          href="/my/likes"
          class="block px-5 py-4 hover:bg-white/10"
          @click="closeMenu"
        >いいね</Link>
        <Link
          href="/ranking"
          class="block px-5 py-4 hover:bg-white/10"
          @click="closeMenu"
        >キャスト</Link>
        <Link
          href="/shifts"
          class="block px-5 py-4 hover:bg-white/10"
          @click="closeMenu"
        >スケジュール</Link>
        <Link
          href="/newbies"
          class="block px-5 py-4 hover:bg-white/10"
          @click="closeMenu"
        >NEW FACE</Link>
        <Link
          href="/events"
          class="block px-5 py-4 hover:bg-white/10"
          @click="closeMenu"
        >EVENT</Link>
        <Link
          href="/staffblog"
          class="block px-5 py-4 hover:bg-white/10"
          @click="closeMenu"
        >STAFF BLOG</Link>
        <Link
          href="/hotels"
          class="block px-5 py-4 hover:bg-white/10"
          @click="closeMenu"
        >HOTEL LIST</Link>
        <Link
          href="/recruit"
          class="block px-5 py-4 hover:bg-white/10"
          @click="closeMenu"
        >RECRUIT</Link>

        <Link
          href="/mypage"
          class="block px-5 py-4 hover:bg-white/10"
          @click="closeMenu"
        >マイページ</Link>

        <Link
          href="/logout"
          class="block px-5 py-4 hover:bg-white/10"
          @click="closeMenu"
        >ログアウト</Link>
      </nav>

      <!-- 下部に余白 -->
      <div class="h-8"></div>
    </aside>
  </div>
</template>
