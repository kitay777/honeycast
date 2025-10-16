<script setup>
const props = defineProps({ items: Object })
</script>

<template>
  <div class="p-6 text-black">
    <div class="flex justify-between mb-4">
      <h1 class="text-xl font-bold">新着情報</h1>
      <a href="/admin/news/create" class="px-3 py-2 bg-emerald-500 rounded">新規</a>
    </div>
    <table class="w-full text-sm">
      <thead><tr><th class="text-left">タイトル</th><th>公開</th><th>日時</th><th></th></tr></thead>
      <tbody>
        <tr v-for="n in props.items.data" :key="n.id" class="border-b border-white/10">
          <td class="py-2 pr-3">{{ n.title }}</td>
          <td class="py-2 text-center">{{ n.is_active ? 'ON' : 'OFF' }}</td>
          <td class="py-2 text-center">{{ n.published_at ?? '—' }}</td>
          <td class="py-2 text-right">
            <a :href="`/admin/news/${n.id}/edit`" class="text-sky-400">編集</a>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
