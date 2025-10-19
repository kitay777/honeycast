<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'
const props = defineProps({ items: Object })
</script>

<template>
  <AppLayout>
    <div class="px-4 py-6 text-white/90 bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%]">
      <div class="max-w-5xl mx-auto">
        <div class="flex items-center justify-between mb-4">
          <h1 class="text-2xl font-bold">送ったギフト</h1>
          <Link href="/dashboard" class="underline hover:opacity-80">← ダッシュボードへ</Link>
        </div>

        <div v-if="!props.items.data?.length" class="text-white/70 py-10 text-center">
          まだギフトを送っていません。
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
          <div v-for="g in props.items.data" :key="g.id" class="bg-white/10 border border-white/20 rounded-xl p-3">
            <div class="flex gap-3">
              <!-- ★ ギフト画像 -->
              <img v-if="g.gift?.image_url" :src="g.gift.image_url" class="h-16 w-16 rounded object-contain bg-white/10" />
              <div class="flex-1">
                <div class="text-sm text-white/70">{{ g.created_at }}</div>
                <div class="font-semibold">
                  {{ g.gift?.name || 'ギフト' }}
                  <span class="ml-2 text-xs text-white/60">→ {{ g.cast?.nickname || `Cast#${g.cast?.id}` }}</span>
                </div>
                <div class="text-xs text-white/70 mt-0.5">
                  消費：<b>{{ g.present_points.toLocaleString() }}</b> pt
                </div>
                <div v-if="g.message" class="mt-1 text-sm text-white/85 whitespace-pre-line">
                  「{{ g.message }}」
                </div>
              </div>
            </div>
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
