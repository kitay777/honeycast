<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import CastCard from '@/Components/Cast/CastCard.vue'
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3' 

/* ====== 受け取り props ====== */
const props = defineProps({
  // 検索関連
  search_applied: { type: Boolean, default: false },
  search_results: { type: Array,  default: () => [] },
  search_filters: { type: Object, default: () => ({}) },

  // 通常ダッシュボード
  today:   { type: Array, default: () => [] },
  login:   { type: Array, default: () => [] },
  newbies: { type: Array, default: () => [] },
  roster:  { type: Array, default: () => [] },
})

/* ====== 下段タブ ====== */
const tabs = [
  { key: 'login',   label: 'ログイン順' },
  { key: 'newbies', label: '新規登録順' },
  { key: 'roster',  label: '在籍一覧' },
]
const current = ref('login')

const lists = computed(() => ({
  login: props.login,
  newbies: props.newbies,
  roster: props.roster,
}))
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

/* ====== レール共通：参照とスクロール関数 ====== */
const railSearch = ref(null)
const railToday  = ref(null)
const railTab    = ref(null)

const scrollBy = (elRef, dir = 1) => {
  const el = elRef?.value
  if (!el) return
  // ビューポート幅の ~90% 分動かすと気持ちよくページング
  const delta = Math.round(el.clientWidth * 0.9) * dir
  el.scrollBy({ left: delta, behavior: 'smooth' })
}
</script>

<template>
  <AppLayout>
    <div class="pt-6 pb-28 px-4 text-white/90
                bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%]">

      <!-- =========================
           検索結果（横スクロール）
           ========================= -->
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
                <CastCard :cast="c" :liked="true" :online="true" />
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
    </div>
  </AppLayout>
</template>
