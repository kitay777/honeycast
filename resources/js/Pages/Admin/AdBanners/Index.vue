<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
const props = defineProps({ items: Object })
</script>

<template>
  <AdminLayout active-key="Adbanners">
  <div class="p-6 text-blcack">
    <div class="flex justify-between mb-4">
      <h1 class="text-xl font-bold">Ad Banners</h1>
      <a href="/admin/ad-banners/create" class="px-3 py-2 bg-emerald-500 rounded">新規</a>
    </div>
    <div class="grid md:grid-cols-2 gap-4">
      <div v-for="it in props.items.data" :key="it.id" class="p-3 bg-white/10 rounded">
        <div class="mb-2">ID: {{ it.id }} / {{ it.is_active ? 'ON' : 'OFF' }}</div>
        <img :src="it.image_url" alt="" class="max-h-32 object-contain bg-black/30 rounded w-full" />
        <div class="mt-2 text-xs">H={{ it.height }} / P={{ it.priority }}</div>
        <a :href="`/admin/ad-banners/${it.id}/edit`" class="text-sky-400 text-sm">編集</a>
      </div>
    </div>
  </div>
  </AdminLayout>
</template>
