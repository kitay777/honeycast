<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({ conversations: { type: Array, default: () => [] } })
</script>

<template>
  <AppLayout>
    <div class="pt-6 pb-28 px-4 text-white/90
                bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%]">
      <h1 class="text-center text-2xl tracking-[0.6em] mb-4">メッセージ一覧</h1>
      <div class="h-[2px] w-full bg-gradient-to-r from-[#c8a64a] via-[#e6d08a] to-[#c8a64a] mb-4"></div>

      <div v-for="row in props.conversations" :key="row.id"
           class="flex items-center gap-3 py-3 border-b border-white/10">
        <!-- アイコン -->
        <div class="w-12 h-12 rounded-full bg-white/10 overflow-hidden shrink-0">
          <img v-if="row.avatar" :src="`/storage/${row.avatar}`" class="w-full h-full object-cover" />
        </div>
        <!-- 本文 -->
        <Link :href="`/messages/${row.id}`" class="flex-1 min-w-0">
          <div class="flex items-center justify-between">
            <div class="text-lg font-semibold truncate">{{ row.name }}</div>
            <div class="text-xs opacity-70">{{ row.at }}</div>
          </div>
          <div class="text-sm opacity-80 truncate">{{ row.snippet }}</div>
        </Link>
        <!-- 未読バッジ -->
        <div v-if="row.unread" class="ml-2 px-2 py-0.5 rounded-full bg-pink-500 text-white text-xs">
          {{ row.unread }}
        </div>
      </div>

      <div v-if="!props.conversations.length" class="text-center opacity-70 py-10">
        メッセージはまだありません。
      </div>
    </div>
  </AppLayout>
</template>
