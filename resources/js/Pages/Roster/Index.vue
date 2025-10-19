<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import CastCard from '@/Components/Cast/CastCard.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  items:   Object,              // pagination
  filters: Object,
  today:   String,
})

const q = ref(props.filters?.q ?? '')
const search = () => router.get(route('roster.index'), { q: q.value }, { preserveScroll: true, replace: true })
</script>

<template>
  <AppLayout>
    <div class="px-4 py-4 text-white/90 bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%]">
      <div class="grid grid-cols-2 gap-2 mb-3">
        <Link href="/shifts" class="py-2 rounded border border-white/20 text-white/80 text-center">本日出勤</Link>
        <button class="py-2 rounded border border-sky-400 bg-sky-500 text-white font-bold text-center">在籍一覧</button>
      </div>


      <!-- 検索 -->
      <div class="flex gap-2 mb-4">
        <input v-model="q" @keyup.enter="search" placeholder="名前で検索"
               class="flex-1 px-3 py-2 rounded bg-white/10 border border-white/20 placeholder-white/50" />
        <button @click="search" class="px-3 py-2 rounded bg-emerald-500 hover:brightness-110">検索</button>
      </div>

      <!-- リスト -->
      <div v-if="!props.items.data.length" class="text-white/70 py-10 text-center">
        在籍者が見つかりません。
      </div>

      <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-4">
        <div v-for="c in props.items.data" :key="c.id" class="relative">
          <CastCard :cast="c" :liked="c.liked" />
          <div class="mt-1 text-center text-sm"
               :class="c.next_at ? 'text-white/80' : 'text-white/50'">
            次回：{{ c.next_badge }}
          </div>
            <!-- 予定リスト（最大5件） -->
          <div v-if="c.upcoming_shifts && c.upcoming_shifts.length"
              class="mt-1 flex flex-wrap justify-center gap-1">
            <span v-for="(s, idx) in c.upcoming_shifts.slice(0,5)"
                  :key="idx"
                  class="px-2 py-0.5 rounded-full text-[11px] bg-white/10 border border-white/20">
              {{ s.label }}
            </span>
          </div>
        </div>
      </div>

      <!-- ページネーション -->
      <div class="mt-6 flex justify-center gap-2" v-if="props.items.links?.length">
        <Link v-for="(l,i) in props.items.links" :key="i" :href="l.url || '#'"
              class="px-3 py-1 rounded bg-white/10 border border-white/20"
              :class="{ 'opacity-50 pointer-events-none': !l.url, '!bg-emerald-600': l.active }"
              v-html="l.label"/>
      </div>
    </div>
  </AppLayout>
</template>
