<!-- resources/js/Pages/Games/Show.vue -->
<script setup>
import { Head } from '@inertiajs/vue3'
const props = defineProps({ game: Object })
</script>

<template>
  <Head :title="props.game.title" />
  <div class="min-h-dvh bg-black text-white px-4 py-5">
    <h1 class="text-lg font-semibold mb-3">{{ props.game.title }}</h1>
    <div class="rounded-lg overflow-hidden border border-white/10 bg-white/5">
      <video :src="props.game.video_url"
             :poster="props.game.poster_url || undefined"
             controls playsinline class="w-full aspect-video"></video>
    </div>
    <p class="mt-3 text-sm opacity-85 whitespace-pre-line">{{ props.game.description }}</p>
  </div>
</template>
