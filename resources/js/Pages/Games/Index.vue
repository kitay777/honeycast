<!-- resources/js/Pages/Games/Index.vue -->
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
const props = defineProps({ games: Array })

</script>

<template>
  <Head title="動画一覧" />
<AppLayout>
  <div class="min-h-dvh bg-black text-white px-4 py-5">
    <h1 class="text-xl font-semibold mb-4">動画一覧</h1>
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
      <Link v-for="g in props.games" :key="g.id" :href="`/games/${g.slug}`"
            class="block rounded-lg overflow-hidden bg-white/5 border border-white/10">
        <img v-if="g.poster_url" :src="g.poster_url" class="w-full h-32 object-cover" />
        <video v-else :src="g.video_url" class="w-full h-32 object-cover" muted playsinline></video>
        <div class="p-2 text-sm line-clamp-2">{{ g.title }}</div>
      </Link>
    </div>
  </div>
  </AppLayout>
</template>
