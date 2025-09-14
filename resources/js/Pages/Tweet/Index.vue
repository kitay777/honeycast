<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  tweets: Object, // ページネーション
})
</script>

<template>
  <AppLayout>
    <div class="pt-6 pb-28 px-4 text-white/90
                bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%]">

      <h1 class="text-center text-2xl mb-6">ツイート</h1>

      <div v-for="t in props.tweets.data" :key="t.id" class="mb-6 bg-[#2b241b]/60 rounded p-4 shadow">
        <div class="flex items-center justify-between mb-2">
          <div class="flex items-center gap-2">
            <img v-if="t.cast_profile?.photo_path" :src="`/storage/${t.cast_profile.photo_path}`"
                 class="w-8 h-8 rounded-full object-cover"/>
            <div class="font-semibold">
              {{ t.cast_profile?.nickname ?? t.user?.name ?? '名無し' }}
            </div>
          </div>
          <div class="text-xs opacity-70">{{ new Date(t.created_at).toLocaleString('ja-JP') }}</div>
        </div>

        <div v-if="t.title" class="font-bold mb-1">{{ t.title }}</div>
        <div class="whitespace-pre-wrap mb-2">{{ t.body }}</div>
        <img v-if="t.image_path" :src="`/storage/${t.image_path}`" class="w-full rounded"/>
      </div>

      <div class="mt-6 text-center">
        <Link href="/tweets/create" class="underline text-yellow-200">新規ツイート</Link>
      </div>
    </div>
  </AppLayout>
</template>
