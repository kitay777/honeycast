<!-- resources/js/Pages/Chat/Index.vue -->
<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  threads: Object, // LaravelのLengthAwarePaginator
})

const me = computed(() => usePage().props?.auth?.user)
</script>

<template>
  <AppLayout>
    <div class="mx-auto max-w-[780px] px-4 py-4 text-black">
      <h1 class="text-xl font-semibold mb-3">チャット</h1>

      <div v-if="!props.threads.data.length" class="text-gray-500">スレッドはまだありません。</div>

      <ul class="divide-y">
        <li v-for="t in props.threads.data" :key="t.id">
          <Link :href="route('chat.show', t.id)" class="flex items-start gap-3 py-3 hover:bg-black/5 rounded px-2 -mx-2">
            <!-- 相手の頭文字アイコン（簡易）-->
            <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-sm font-semibold">
              {{ (t.other?.name || '？').slice(0,2) }}
            </div>

            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <div class="font-semibold truncate">
                  {{ t.other?.name || '相手不明' }}
                </div>
                <div v-if="t.unread_count > 0"
                     class="ml-auto bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">
                  {{ t.unread_count }}
                </div>
              </div>

              <div class="text-sm text-gray-600 truncate">
                {{ t.last_message?.body || '（メッセージなし）' }}
              </div>

              <div class="text-xs text-gray-400">
                {{ t.updated_at ? new Date(t.updated_at).toLocaleString() : '' }}
              </div>
            </div>
          </Link>
        </li>
      </ul>

      <!-- ページネーション（必要に応じて） -->
      <div v-if="props.threads.links?.length" class="flex gap-2 mt-4">
        <Link v-for="l in props.threads.links" :key="l.url || l.label"
              :href="l.url || '#'" :class="[
                'px-3 py-1 rounded border',
                l.active ? 'bg-black text-white' : 'bg-white',
                !l.url ? 'opacity-50 pointer-events-none' : ''
              ]" v-html="l.label" />
      </div>
    </div>
  </AppLayout>
</template>
