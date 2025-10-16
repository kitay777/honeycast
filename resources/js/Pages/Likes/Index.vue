<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import CastCard from '@/Components/Cast/CastCard.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  items: Object, // pagination
})
</script>

<template>
  <AppLayout>
    <div class="px-4 py-6 text-white/90
                bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%]">
      <div class="max-w-6xl mx-auto">
        <div class="flex items-center justify-between mb-4">
          <h1 class="text-2xl font-bold">いいねしたキャスト</h1>
          <Link href="/dashboard" class="text-sm underline hover:opacity-80">← ダッシュボードへ</Link>
        </div>

        <div v-if="!props.items.data.length" class="text-white/70">
          まだ「いいね」したキャストがいません。
        </div>

        <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
          <div v-for="c in props.items.data" :key="c.id">
            <CastCard :cast="c" :liked="true" />
          </div>
        </div>

        <!-- ページネーション -->
        <div class="mt-6 flex justify-center gap-2" v-if="props.items.links?.length">
          <Link v-for="(l,i) in props.items.links" :key="i" :href="l.url || '#'"
                class="px-3 py-1 rounded bg-white/10 border border-white/20"
                :class="{ 'opacity-50 pointer-events-none': !l.url, '!bg-emerald-600': l.active }"
                v-html="l.label" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>
