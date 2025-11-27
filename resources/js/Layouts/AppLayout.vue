<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { ref, onMounted, onBeforeUnmount, watch } from "vue";

const page = usePage()

const user = page.props.auth?.user || null
console.log(user)
const castProfile = page.props.auth?.cast_profile || null

const isCast = !!castProfile
const modal = ref(page.props.flash?.modal ?? null);
watch(
    () => page.props.flash,
    (f) => {
        modal.value = f?.modal ?? null;
    }
);

const closeModal = () => {
    modal.value = null;
};

const isMenuOpen = ref(false);
const openMenu = () => {
    isMenuOpen.value = true;
};
const closeMenu = () => {
    isMenuOpen.value = false;
};

// ★ モーダル or メニュー表示時は body スクロールを止める
const toggleBodyScroll = () => {
    const locked = !!modal.value || isMenuOpen.value;
    document.documentElement.classList.toggle("overflow-hidden", locked);
};
watch(modal, toggleBodyScroll);
watch(isMenuOpen, toggleBodyScroll);

// ESC で「まずモーダル→次にメニュー」を閉じる
const onKeydown = (e) => {
    if (e.key !== "Escape") return;
    if (modal.value) return closeModal();
    if (isMenuOpen.value) return closeMenu();
};

onMounted(() => window.addEventListener("keydown", onKeydown));
onBeforeUnmount(() => {
    window.removeEventListener("keydown", onKeydown);
    document.documentElement.classList.remove("overflow-hidden");
});
</script>

<template>
    <div class="min-h-dvh bg-black text-white">
        <!-- ヘッダー固定 -->
        <header
            class="fixed top-0 left-0 right-0 z-50 bg-[#4b3621] text-yellow-200 flex justify-around py-3 shadow h-16"
        >
            <Link href="/dashboard" class="flex flex-col items-center">
                <img
                    :src="
                        page.url === '/dashboard'
                            ? '/assets/icons/topon.png'
                            : '/assets/icons/top.png'
                    "
                    class="h-12 mb-1"
                    alt="Top"
                />
            </Link>

            <Link href="/roster" class="flex flex-col items-center">
                <img
                    :src="
                        page.url === '/roster'
                            ? '/assets/icons/caston.png'
                            : '/assets/icons/cast.png'
                    "
                    class="h-12 mb-1"
                    alt="Roster"
                />
            </Link>

            <Link href="/system" class="flex flex-col items-center">
                <img
                    :src="
                        page.url === '/system'
                            ? '/assets/icons/systemon.png'
                            : '/assets/icons/system.png'
                    "
                    class="h-12 mb-1"
                    alt="System"
                />
            </Link>

<a
  href="https://fujoho.jp/index.php?p=shop_info&id=68273"
  target="_blank"
  rel="noopener noreferrer"
  class="flex flex-col items-center"
>
  <img
    :src="
      page.url === '/shifts'
        ? '/assets/icons/schon.png'
        : '/assets/icons/sch.png'
    "
    class="h-12 mb-1"
    alt="Likes"
  />
</a>

            <Link href="/contact" class="flex flex-col items-center">
                <img
                    :src="
                        page.url === '/contact'
                            ? '/assets/icons/contacton.png'
                            : '/assets/icons/contact.png'
                    "
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
                <img
                    src="/assets/icons/menu.png"
                    class="h-12 mb-1"
                    alt="Menu"
                />
            </button>
        </header>

        <!-- ★ ポップアップ（簡易モーダル） -->
        <!-- 背景クリックで閉じる -->
        <div
            v-if="modal"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60"
            @click.self="closeModal"
            role="dialog"
            aria-modal="true"
            :aria-label="modal.title || 'お知らせ'"
        >
            <div
                class="bg-white text-black w-[92vw] max-w-md rounded-2xl p-6 shadow-xl"
            >
                <h3 class="text-xl font-bold mb-2">
                    {{ modal.title || "お知らせ" }}
                </h3>
                <p class="mb-6 whitespace-pre-line">{{ modal.message }}</p>
                <div class="text-right">
                    <button
                        @click="closeModal"
                        class="px-4 py-2 rounded bg-gray-900 text-white"
                    >
                        閉じる
                    </button>
                </div>
            </div>
        </div>
        <!-- コンテンツ部分 -->
        <main class="pt-16 pb-20 overflow-y-auto">
            <slot />
        </main>

        <!-- フッター固定 -->
        <footer
            class="fixed bottom-0 left-0 right-0 z-50 bg-[#4b3621] text-yellow-200 grid grid-cols-5 items-center h-20 shadow"
        >
            <Link href="/search" class="justify-self-center"
                ><img src="/assets/icons/search.png" class="h-12" alt="Search"
            /></Link>
            <Link href="/tweets" class="justify-self-center"
                ><img src="/assets/icons/tweet.png" class="h-12" alt="Tweets"
            /></Link>

            <!-- 中央セル（丸いボタン） -->
            <div class="relative justify-self-center -mt-8">
                <Link href="/call" class="block w-24 aspect-square">
                    <img
                        src="/assets/icons/call.png"
                        class="w-full h-full object-contain"
                        alt="Call"
                    />
                </Link>
            </div>

            <Link href="/reservations" class="justify-self-center"
                ><img
                    src="/assets/icons/reserve.png"
                    class="h-12"
                    alt="Reserve"
            /></Link>
            <Link href="/chat" class="justify-self-center"
                ><img
                    src="/assets/icons/message.png"
                    class="h-12"
                    alt="Message"
            /></Link>
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
            class="fixed top-0 right-0 z-[80] h-dvh w-80 max-w-[85vw] bg-[#1b1b1b] text-white shadow-2xl transition-transform duration-300 ease-out overflow-y-auto"
            :class="isMenuOpen ? 'translate-x-0' : 'translate-x-full'"
            @click.stop
        >
            <!-- ヘッダー -->
            <div
                class="flex items-center justify-between h-16 px-4 border-b border-white/10"
            >
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
                    >TOP</Link
                >
                <Link
                    href="/system"
                    class="block px-5 py-4 hover:bg-white/10"
                    @click="closeMenu"
                    >SYSTEM</Link
                >
                <Link
                    href="/tweets"
                    class="block px-5 py-4 hover:bg-white/10"
                    @click="closeMenu"
                    >ツイート</Link
                >
                <Link
                    href="/my/likes"
                    class="block px-5 py-4 hover:bg-white/10"
                    @click="closeMenu"
                    >いいね</Link
                >
                <Link
                    href="/roster"
                    class="block px-5 py-4 hover:bg-white/10"
                    @click="closeMenu"
                    >キャスト</Link
                >
                <Link
                    href="/shifts"
                    class="block px-5 py-4 hover:bg-white/10"
                    @click="closeMenu"
                    >スケジュール</Link
                >
                <Link
                    href="/newbies"
                    class="block px-5 py-4 hover:bg-white/10"
                    @click="closeMenu"
                    >NEW FACE</Link
                >
                <Link
                    href="/events"
                    class="block px-5 py-4 hover:bg-white/10"
                    @click="closeMenu"
                    >EVENT</Link
                >
                <Link
                    href="/staffblog"
                    class="block px-5 py-4 hover:bg-white/10"
                    @click="closeMenu"
                    >STAFF BLOG</Link
                >
                <Link
                    href="/hotels"
                    class="block px-5 py-4 hover:bg-white/10"
                    @click="closeMenu"
                    >HOTEL LIST</Link
                >
                <Link
                    v-if="
                        page.props.auth?.user &&
                        (page.props.auth.user.is_admin ||
                            page.props.auth.user.is_shop_owner)
                    "
                    href="/cast/qr"
                    class="block px-5 py-4 hover:bg-white/10"
                    @click="closeMenu"
                >
                    RECRUIT
                </Link>
                <Link
                    href="/mypage/account"
                    class="block px-5 py-4 hover:bg-white/10"
                    @click="closeMenu"
                    >アカウント</Link
                >

<template v-if="isCast">
                <Link
                    href="/mypage"
                    class="block px-5 py-4 hover:bg-white/10"
                    @click="closeMenu"
                    >マイページ</Link
                >

                <Link
                    href="/cast/match"
                    class="block px-5 py-4 hover:bg-white/10"
                    @click="closeMenu"
                    >スタート</Link
                >
                <Link
                    href="/cast/match"
                    class="block px-5 py-4 hover:bg-white/10"
                    @click="closeMenu"
                    >終了/延長</Link
                >
</template>

                <Link
                    href="/mypage/points"
                    class="block px-5 py-4 hover:bg-white/10"
                    @click="closeMenu"
                    >ポイント</Link
                >
                <Link
                    href="/my/gifts"
                    class="block px-5 py-4 hover:bg-white/10"
                    @click="closeMenu"
                    >送ったギフト</Link
                >
                <Link
                    href="/cast/gifts"
                    class="block px-5 py-4 hover:bg-white/10"
                    @click="closeMenu"
                    >貰ったギフト</Link
                >
                <Link
                    href="/my/receipts"
                    class="block px-5 py-4 hover:bg-white/10"
                    @click="closeMenu"
                    >領収書</Link
                >
                 <!-- ✅ ログイン中だけ表示 -->
    <div v-if="user">
      <span class="mr-3">{{ user.name }}</span>
      <Link
        method="post"
        as="button"
        :href="route('logout')"
        class="px-3 py-1 bg-red-600 rounded hover:bg-red-700"
      >
        ログアウト
      </Link>
    </div>
    <div v-else>
              <Link
                  href="/login"
                  class="block px-5 py-4 hover:bg-white/10"
                  @click="closeMenu"
                  >ログイン</Link
              >
              </div>    
            </nav>

            <!-- 下部に余白 -->
            <div class="h-8"></div>
        </aside>
    </div>
</template>
