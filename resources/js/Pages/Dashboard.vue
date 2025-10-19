<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import CastCard from '@/Components/Cast/CastCard.vue'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3' 

/* ====== 受け取り props（←最初に定義） ====== */
const props = defineProps({
  search_applied: { type: Boolean, default: false },
  search_results: { type: Array,  default: () => [] },
  search_filters: { type: Object, default: () => ({}) },
  today:   { type: Array, default: () => [] },
  login:   { type: Array, default: () => [] },
  newbies: { type: Array, default: () => [] },
  roster:  { type: Array, default: () => [] },
  text_banners: { type: Array, default: () => [] },
  ad_banners:   { type: Array, default: () => [] },
  news: { type: Array, default: () => [] },
})

/* ====== 画像広告カルーセル（軽量版） ====== */
const slide = ref(0)
const transitioning = ref(false)
const intervalMs = 3500 // 次のスライドまでの待ち時間
const durationMs  = 500  // アニメ時間
let timer = null

const adCount = computed(() => props.ad_banners.length)
const visibleTextBanners = computed(() =>
  (props.text_banners ?? []).filter(tb =>
    tb && tb.is_active !== false && String(tb.message ?? '').trim().length > 0
  )
)
const go = (idx) => {
  if (!adCount.value) return
  const next = (idx + adCount.value) % adCount.value
  if (next === slide.value) return
  transitioning.value = true
  slide.value = next
  window.setTimeout(() => (transitioning.value = false), durationMs)
}
const nextAd = () => go(slide.value + 1)
const prevAd = () => go(slide.value - 1)

const start = () => { stop(); if (adCount.value > 1) timer = setInterval(nextAd, intervalMs) }
const stop  = () => { if (timer) { clearInterval(timer); timer = null } }
const handleVisibility = () => { document.hidden ? stop() : start() }

onMounted(() => {
  start()
  document.addEventListener('visibilitychange', handleVisibility, false)
})
onUnmounted(() => {
  stop()
  document.removeEventListener('visibilitychange', handleVisibility, false)
})

/* ====== 既存の計算系 ====== */
const tabs = [
  { key: 'login',   label: 'ログイン順' },
  { key: 'newbies', label: '新規登録順' },
  { key: 'roster',  label: '在籍一覧' },
]
const current = ref('login')
const lists = computed(() => ({ login: props.login, newbies: props.newbies, roster: props.roster }))
const displayed = computed(() => lists.value[current.value] ?? [])
const counts = computed(() => ({
  login:   props.login?.length   ?? 0,
  newbies: props.newbies?.length ?? 0,
  roster:  props.roster?.length  ?? 0,
}))
const page = usePage()
const isShopOwner = computed(() => {
  const u = page.props?.auth?.user
  return !!(u?.is_shop_owner && u?.shop_id)
})
const railSearch = ref(null)
const railToday  = ref(null)
const railTab    = ref(null)
const scrollBy = (elRef, dir = 1) => {
  const el = elRef?.value
  if (!el) return
  const delta = Math.round(el.clientWidth * 0.9) * dir
  el.scrollBy({ left: delta, behavior: 'smooth' })
}
const bannerStyle = computed(() => {
  const first = visibleTextBanners.value[0] ?? {}
  return {
    bg: first.bg_color || '#111111',
    color: first.text_color || '#FFE08A',
    speed: first.speed || 60,
  }
})
const marqueeDuration = computed(() =>
  `${Math.max(8, 2000 / (bannerStyle.value.speed || 60))}s`
)
</script>

<template>
  <AppLayout>
    <div class="pt-6 pb-28 px-4 text-white/90
                bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%]">

      <!-- =========================
           検索結果（横スクロール）
           ========================= -->
           <!-- ===== テキスト・マルチ（右→左） ===== -->
<!-- １本帯のテキストバナー -->
<section v-if="visibleTextBanners.length" class="mb-3">
  <div class="relative overflow-hidden rounded-md"
       :style="{ backgroundColor: bannerStyle.bg, color: bannerStyle.color }">

    <!-- 動かすトラック（CSS変数で速度を渡す） -->
    <div class="marquee-track"
         :style="{ '--dur': marqueeDuration }">
      <!-- 2周分を連結 -->
      <div class="marquee-inner" v-for="rep in 2" :key="rep">
        <template v-for="(tb, i) in visibleTextBanners" :key="`${rep}-${tb.id}`">
          <component :is="tb.url ? 'a' : 'span'"
                     :href="tb.url || undefined" target="_blank" rel="noopener"
                     class="inline-block px-4 py-2 hover:underline">
            {{ tb.message }}
          </component>
          <span v-if="i !== visibleTextBanners.length - 1"
                aria-hidden="true"
                class="opacity-60 px-2">|</span>
        </template>
      </div>
    </div>

  </div>
</section>


<!-- ===== 画像広告（1枚ずつスライド：軽量） ===== -->
<section v-if="props.ad_banners.length" class="mb-4">
  <div class="relative overflow-hidden rounded-md bg-black/30 h-[400px]"
       @mouseenter="stop" @mouseleave="start">

    <!-- トラック：幅は100%。translateX(-slide*100%) で移動 -->
    <div class="ad-track"
         :style="{
           transform: `translateX(-${slide * 100}%)`,
           transition: transitioning ? `transform ${durationMs}ms ease-out` : 'none',
         }">
      <a v-for="ad in props.ad_banners" :key="ad.id"
         :href="ad.url || undefined" target="_blank" rel="noopener"
         class="ad-slide">
<img :src="ad.src" :alt="`ad-${ad.id}`" class="ad-img object-contain" />
      </a>
    </div>

    <!-- 左右ボタン -->
    <button class="hidden md:flex absolute left-2 top-1/2 -translate-y-1/2 z-10
                   h-9 w-9 items-center justify-center rounded-full bg-black/40 hover:bg-black/60"
            @click="prevAd" aria-label="prev">‹</button>
    <button class="hidden md:flex absolute right-2 top-1/2 -translate-y-1/2 z-10
                   h-9 w-9 items-center justify-center rounded-full bg-black/40 hover:bg-black/60"
            @click="nextAd" aria-label="next">›</button>

    <!-- ドット -->
    <div v-if="props.ad_banners.length > 1"
         class="absolute bottom-2 left-0 right-0 flex justify-center gap-2 z-10">
      <button v-for="(ad,i) in props.ad_banners" :key="ad.id"
              class="h-2.5 w-2.5 rounded-full transition"
              :class="i === slide ? 'bg-white' : 'bg-white/40 hover:bg-white/70'"
              @click="go(i)" aria-label="go"></button>
    </div>
  </div>
</section>


      <section v-if="props.search_applied" class="mb-8">
        <div class="inline-block px-4 py-1 rounded bg-[#6b4b17] border border-[#d1b05a] text-[18px] tracking-[0.3em]">
          検索結果（{{ props.search_results.length }}）
        </div>

        <div class="relative mt-3">
          <!-- 左右ボタン（md以上で表示） -->
          <button
            class="hidden md:flex absolute left-0 top-1/2 -translate-y-1/2 z-10
                   h-10 w-10 items-center justify-center rounded-full bg-black/40 hover:bg-black/60"
            @click="scrollBy(railSearch, -1)">‹</button>
          <button
            class="hidden md:flex absolute right-0 top-1/2 -translate-y-1/2 z-10
                   h-10 w-10 items-center justify-center rounded-full bg-black/40 hover:bg-black/60"
            @click="scrollBy(railSearch, 1)">›</button>

          <!-- 横レール -->
          <div
            ref="railSearch"
            class="flex gap-4 overflow-x-auto no-scrollbar snap-x snap-mandatory scroll-smooth
                   -mx-2 px-2 py-2"
          >
            <div
              v-for="c in props.search_results"
              :key="c.id"
              class="shrink-0 snap-start w-[68vw] xs:w-[55vw] sm:w-[240px] md:w-[260px]"
            >
              <CastCard :cast="c" />
            </div>
          </div>
        </div>

        <div class="mt-3">
          <a href="/dashboard" class="underline text-yellow-200 text-sm">検索をクリア</a>
        </div>
      </section>

      <!-- =========================
           通常ダッシュボード（横スクロール）
           ========================= -->
      <template v-else>
        <div v-if="isShopOwner" class="mb-4 text-right">
          <Link href="/my/shop"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-md
                      bg-yellow-200 text-black font-semibold shadow hover:brightness-110">
            <span>🛍️ マイショップ</span>
          </Link>
        </div>

        <!-- 上段：本日呼べる（横レール） -->
        <section v-if="props.today && props.today.length" class="mb-8">
          <div class="inline-block px-4 py-1 rounded bg-[#6b4b17] border border-[#d1b05a]
                      text-[18px] tracking-[0.3em] mb-2">
            本日 呼べる CAST
          </div>

          <div class="relative">
            <button
              class="hidden md:flex absolute left-0 top-1/2 -translate-y-1/2 z-10
                     h-10 w-10 items-center justify-center rounded-full bg-black/40 hover:bg-black/60"
              @click="scrollBy(railToday, -1)">‹</button>
            <button
              class="hidden md:flex absolute right-0 top-1/2 -translate-y-1/2 z-10
                     h-10 w-10 items-center justify-center rounded-full bg-black/40 hover:bg-black/60"
              @click="scrollBy(railToday, 1)">›</button>

            <div
              ref="railToday"
              class="flex gap-4 overflow-x-auto no-scrollbar snap-x snap-mandatory scroll-smooth
                     -mx-2 px-2 py-2"
            >
              <div
                v-for="c in props.today"
                :key="c.id"
                class="shrink-0 snap-start w-[68vw] xs:w-[55vw] sm:w-[240px] md:w-[260px]"
              >
                <CastCard :cast="c" :liked="c.liked" :online="true" />
              </div>
            </div>
          </div>
        </section>

        <!-- 下段：タブボタン -->
        <div class="flex gap-2 justify-between md:justify-start md:gap-3 mb-3">
          <button
            v-for="t in tabs"
            :key="t.key"
            @click="current = t.key"
            class="flex-1 md:flex-none px-3 py-2 rounded-md border
                   text-sm md:text-base tracking-widest transition shadow
                   focus:outline-none focus:ring-2 focus:ring-yellow-300"
            :class="current === t.key
                ? 'bg-[#6b4b17] border-[#d1b05a] text-yellow-200'
                : 'bg-white/10 border-white/30 text-white/80 hover:bg-white/20'">
            {{ t.label }}
            <span class="ml-2 text-xs opacity-80">({{ counts[t.key] }})</span>
          </button>
        </div>

        <!-- 下段：横レール -->
        <div class="relative">
          <button
            class="hidden md:flex absolute left-0 top-1/2 -translate-y-1/2 z-10
                   h-10 w-10 items-center justify-center rounded-full bg-black/40 hover:bg-black/60"
            @click="scrollBy(railTab, -1)">‹</button>
          <button
            class="hidden md:flex absolute right-0 top-1/2 -translate-y-1/2 z-10
                   h-10 w-10 items-center justify-center rounded-full bg-black/40 hover:bg-black/60"
            @click="scrollBy(railTab, 1)">›</button>

          <div
            ref="railTab"
            class="flex gap-4 overflow-x-auto no-scrollbar snap-x snap-mandatory scroll-smooth
                   -mx-2 px-2 py-2 min-h-40"
          >
            <div
              v-for="c in displayed"
              :key="c.id"
              class="shrink-0 snap-start w-[68vw] xs:w-[55vw] sm:w-[240px] md:w-[260px]"
            >
              <CastCard :cast="c" />
            </div>
          </div>

          <div v-if="displayed.length === 0" class="text-center text-white/70 py-10">
            該当のキャストがいません。
          </div>
        </div>



      </template>
              <!-- 新着情報（最新10件） -->
        <section v-if="props.news.length" class="mb-4">
          <div class="rounded-md bg-black/40 border border-yellow-900/40">
            <div class="px-4 py-2 text-yellow-200 tracking-[0.3em] border-b border-yellow-900/40">
              新着情報
            </div>
            <ul class="divide-y divide-white/10">
              <li v-for="n in props.news" :key="n.id" class="px-4 py-3">
                <div class="flex flex-col sm:flex-row sm:items-center sm:gap-3">
                  <time class="text-xs text-white/60 min-w-28">
                    {{ n.published_at ?? '' }}
                  </time>
                  <div class="flex-1">
                    <component :is="n.url ? 'a' : 'span'"
                              :href="n.url || undefined" target="_blank" rel="noopener"
                              class="font-semibold hover:underline">
                      {{ n.title }}
                    </component>
                    <p v-if="n.body" class="text-sm text-white/80 mt-1 line-clamp-2">
                      {{ n.body }}
                    </p>
                  </div>
                </div>
              </li>
            </ul>
          </div>
        </section>
        <div class="text-sm text-white/80">&copy; 2025 choco</div>
          <div class="mt-2 text-xs leading-relaxed text-white/70 space-y-1">
            <p>当店はエロ＋飲みのギャラ飲みのエロバーションです。</p>
            <p>ピンクコンパニオンの都内バージョンのイメージです。</p>
            <p>1対1はもちろん、団体でのご利用も可能です。</p>
            <p>エ口くて楽しいゲームで盛り上がりましょう。</p>
            <p>
              一般的な性サービスも可能です。詳細は
              <Link href="/system" class="underline underline-offset-2 hover:opacity-80">SYSTEM</Link>
              よりご覧下さい。
            </p>
          </div>
    </div>
  </AppLayout>



</template>

<style>
/* トラック自体を動かす。max-content で中身の幅にフィット */
.marquee-track {
  display: inline-flex;
  align-items: center;
  white-space: nowrap;
  width: max-content;
  animation: marquee-left var(--dur) linear infinite;
  will-change: transform;
}

/* 2周分を横並びにする器（flexでOK） */
.marquee-inner {
  display: inline-flex;
  align-items: center;
}

/* keyframes は “scoped なし” で定義（Safari等での不具合回避） */
@keyframes marquee-left {
  0%   { transform: translateX(0); }
  100% { transform: translateX(-50%); } /* 2周分の半分で継ぎ目なし */
}

/* 画像バナーも同様の考えで動かすならこちらもグローバルに */
@keyframes slide-left {
  0%   { transform: translateX(0); }
  100% { transform: translateX(-50%); }
}
/* 軽量スライダー用 */
.ad-track {
  display: flex;
  width: 100%;
  will-change: transform;
}

/* 各スライドは“枠の幅100% × 高さ100%” */
.ad-slide {
  flex: 0 0 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100%;           /* ← 高さ埋める */
}

/* 画像は“高さ優先（縦400pxに合わせる）・はみ出さない” */
.ad-img {
  height: 100%;           /* ← これが縦優先のキモ */
  width: auto;            /* アスペクト比維持 */
  max-width: 100%;        /* 横にはみ出さない */
  object-fit: contain;    /* トリミングなし（切りたければ cover に変更） */
}

</style>

