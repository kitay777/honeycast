<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({ items: Object, filters: Object })
const q = ref(props.filters?.q ?? '')
const search = () => router.get('/admin/gifts', { q: q.value }, { preserveState: true, replace: true })
</script>

<template>
  <AdminLayout active-key="gifts">
    <div class="p-6 space-y-4">
      <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">プレゼント</h1>
        <div class="flex gap-2">
          <input v-model="q" @keyup.enter="search" placeholder="名称検索" class="px-3 py-2 border rounded" />
          <button @click="search" class="px-3 py-2 rounded bg-black text-white">検索</button>
          <Link href="/admin/gifts/create" class="px-3 py-2 rounded bg-emerald-600 text-white">新規</Link>
        </div>
      </div>

      <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
        <div v-for="g in props.items.data" :key="g.id" class="bg-white rounded-2xl shadow p-3">
          <div class="flex gap-3">
            <img :src="g.image_url" class="h-20 w-24 object-contain bg-gray-50 rounded" />
            <div class="flex-1">
              <div class="font-semibold">{{ g.name }}</div>
              <div class="text-xs text-gray-600 mt-1">
                🧧 {{ g.present_points.toLocaleString() }} pt
                <span class="ml-2">→ 🎁 {{ g.cast_points.toLocaleString() }} pt</span>
              </div>
              <div class="text-xs mt-1" :class="g.is_active ? 'text-emerald-700' : 'text-gray-400'">
                {{ g.is_active ? '公開中' : '非公開' }} / 優先度: {{ g.priority }}
              </div>
            </div>
          </div>
          <div class="mt-2 text-right">
            <Link :href="`/admin/gifts/${g.id}/edit`" class="text-sky-600 text-sm">編集</Link>
          </div>
        </div>
      </div>

      <div class="mt-4 flex gap-2 flex-wrap">
        <Link v-for="(lnk,i) in props.items.links" :key="i"
              :href="lnk.url || '#'" class="px-3 py-1 border rounded"
              :class="[lnk.active ? 'bg-black text-white' : '', !lnk.url ? 'opacity-50 pointer-events-none' : '']"
              v-html="lnk.label" />
      </div>
    </div>
  </AdminLayout>
</template>
