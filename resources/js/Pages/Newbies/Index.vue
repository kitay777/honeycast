<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import CastCard from '@/Components/Cast/CastCard.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  items:   Object,              // pagination
  filters: Object,
  since:   String,              // 3ヶ月の境界
})

const q = ref(props.filters?.q ?? '')
const search = () => router.get(route('newbies.index'), { q: q.value }, { preserveScroll: true, replace: true })
</script>

<template>
  <AppLayout>
    <div class="px-4 py-4 text-white/90 bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%]">
      <!-- タブ（本日出勤 / 在籍一覧 / 新人）を揃える場合 -->

      <div class="mb-3 text-sm text-white/70">
        ※ 新人＝直近3ヶ月（{{ props.since }} 以降に登録）
      </div>

      <!-- 検索 -->
      <div class="flex gap-2 mb-4">
        <input v-model="q" @keyup.enter="search" placeholder="名前で検索"
               class="flex-1 px-3 py-2 rounded bg-white/10 border border-white/20 placeholder-white/50" />
        <button @click="search" class="px-3 py-2 rounded bg-emerald-500 hover:brightness-110">検索</button>
      </div>

      <!-- リスト -->
      <div v-if="!props.items.data.length" class="text-white/70 py-10 text-center">
        直近3ヶ月の新人はまだいません。
      </div>

      <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-4">
        <div v-for="c in props.items.data" :key="c.id" class="relative">
          <CastCard :cast="c" :liked="c.liked" />
          <!-- NEWバッジ / 参加日 -->
          <div class="absolute top-2 left-2" v-if="c.is_new">
            <span class="text-[10px] px-2 py-0.5 rounded bg-pink-600/90">NEW</span>
          </div>
          <div class="mt-1 text-center text-xs text-white/70">
            参加日：{{ c.join_badge }}
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
