<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
const props = defineProps({ items: Object })

</script>

<template>
  <AdminLayout active-key="text-banners">
  <div class="p-6 text-black">
    <div class="flex justify-between mb-4">
      <h1 class="text-xl font-bold">Text Banners</h1>
      <a href="/admin/text-banners/create" class="px-3 py-2 bg-emerald-500 rounded">新規</a>
    </div>
    <table class="w-full text-sm">
      <thead><tr><th>ID</th><th>message</th><th>active</th><th></th></tr></thead>
      <tbody>
        <tr v-for="it in props.items.data" :key="it.id">
          <td>{{ it.id }}</td>
          <td class="truncate max-w-[420px]">{{ it.message }}</td>
          <td>{{ it.is_active ? 'ON' : 'OFF' }}</td>
          <td>
            <a :href="`/admin/text-banners/${it.id}/edit`" class="text-sky-400">編集</a>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  </AdminLayout>
</template>
