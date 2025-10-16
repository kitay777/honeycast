<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
const props = defineProps({ items: Object })
</script>

<template>
  <AdminLayout active-key="Events">
  <div class="p-6 text-black">
    <div class="flex justify-between mb-4">
      <h1 class="text-xl font-bold">イベント</h1>
      <a href="/admin/events/create" class="px-3 py-2 bg-emerald-500 rounded">新規</a>
    </div>
    <table class="w-full text-sm">
      <thead><tr><th class="text-left">タイトル</th><th>開始</th><th>公開</th><th></th></tr></thead>
      <tbody>
        <tr v-for="e in props.items.data" :key="e.id" class="border-b border-white/10">
          <td class="py-2 pr-3">{{ e.title }}</td>
          <td class="py-2 text-center">{{ e.starts_at }}</td>
          <td class="py-2 text-center">{{ e.is_active ? 'ON' : 'OFF' }}</td>
          <td class="py-2 text-right"><a :href="`/admin/events/${e.id}/edit`" class="text-sky-400">編集</a></td>
        </tr>
      </tbody>
    </table>
  </div>
  </AdminLayout>
</template>
