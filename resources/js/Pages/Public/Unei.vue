<!-- resources/js/Pages/Public/Terms.vue -->
<script setup>
import { Head, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue' // 共通レイアウトがなければ素のdivでOK
import { computed } from 'vue'

const props = defineProps({
  version: String,
  mode: String,      // 'public' | 'review'
  accepted: Boolean, // 既に同意済みか
})

const page = usePage()
const isLoggedIn = computed(() => !!page.props?.auth?.user)

const accept = () => {
  router.post(route ? route('terms.accept') : '/terms/accept')
}
</script>

<template>
  <AppLayout>
    <Head :title="`運営 v${version}`" />
    <div class="max-w-3xl mx-auto px-4 py-8 text-white-800">
      <h1 class="text-2xl font-bold mb-4">運営 <span class="text-sm font-normal text-gray-500">v{{ version }}</span></h1>

      <!-- 規約本文（必要に応じて編集） -->
      <div class="prose max-w-none">
        <h2>株式会社LIBRE</h2>
        <p>東京都豊島区南池袋1丁目9番18号130</p>
        <p>LINE: @965grr</p>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.prose h2 { @apply text-lg font-semibold mt-6 mb-2; }
.prose p, .prose ul { @apply mb-3; }
</style>
