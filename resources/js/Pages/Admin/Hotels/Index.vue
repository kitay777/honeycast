<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({ items: Object, filters: Object })

const q = ref(props.filters?.q ?? '')

const search = () => {
  router.get('/admin/hotels', { q: q.value }, { preserveState: true, replace: true })
}
</script>

<template>

  <AdminLayout active-key="Hotels">
  <div class="p-6 text-black">
    <div class="flex justify-between items-center mb-4">
      <h1 class="text-xl font-bold">ホテル</h1>
      <div class="flex gap-2">
        <input v-model="q" @keyup.enter="search" placeholder="名称/住所 検索"
               class="px-3 py-2 rounded bg-white/10"/>
        <button @click="search" class="px-3 py-2 rounded bg-emerald-500">検索</button>
        <Link href="/admin/hotels/create" class="px-3 py-2 bg-sky-600 rounded">新規</Link>
      </div>
    </div>

    <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
      <div v-for="h in props.items.data" :key="h.id" class="p-3 bg-white/10 rounded border border-white/20">
        <div class="flex gap-3">
          <img v-if="h.image" :src="h.image" class="h-16 w-24 object-cover rounded"/>
          <div class="flex-1">
            <div class="font-semibold">{{ h.name }}</div>
            <div class="text-xs text-white/70">{{ h.area }}</div>
            <div class="text-xs mt-1">{{ h.active ? '公開中' : '非公開' }}</div>
          </div>
        </div>
        <div class="mt-2 text-right">
          <Link :href="`/admin/hotels/${h.id}/edit`" class="text-sky-400">編集</Link>
        </div>
      </div>
    </div>
  </div>
  </AdminLayout>
</template>
