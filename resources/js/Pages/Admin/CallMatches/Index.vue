<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, Link } from "@inertiajs/vue3";

const props = defineProps({ matches: Object });
const matches = props.matches.data || [];
</script>

<template>
  <Head title="コール履歴" />
  <AdminLayout active-key="call-matches">
    <template #header>
      <div class="px-5 py-3 bg-white border-b flex justify-between items-center">
        <h1 class="text-xl font-semibold">📞 コール履歴</h1>
      </div>
    </template>

    <div class="p-5 overflow-auto bg-gray-50 min-h-screen">
      <table class="w-full text-sm bg-white shadow rounded-xl border">
        <thead class="bg-gray-100 text-gray-700">
          <tr>
            <th class="px-3 py-2 text-left">ID</th>
            <th class="px-3 py-2 text-left">キャスト</th>
            <th class="px-3 py-2 text-left">ユーザー</th>
            <th class="px-3 py-2 text-left">状態</th>
            <th class="px-3 py-2 text-left">開始</th>
            <th class="px-3 py-2 text-left">終了</th>
            <th class="px-3 py-2 text-left">延長</th>
            <th class="px-3 py-2 text-left">位置情報</th>
            <th class="px-3 py-2"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="m in matches" :key="m.id" class="border-t hover:bg-gray-50">
            <td class="px-3 py-2">{{ m.id }}</td>
            <td class="px-3 py-2">
              {{ m.cast_profile?.nickname || "—" }}
              <div class="text-xs text-gray-500">{{ m.cast_profile?.user?.email }}</div>
            </td>
            <td class="px-3 py-2">
              {{ m.call_request?.user?.name || "—" }}
              <div class="text-xs text-gray-500">{{ m.call_request?.user?.email }}</div>
            </td>
            <td class="px-3 py-2">
              <span
                :class="m.status === 'ended'
                  ? 'bg-red-100 text-red-700'
                  : 'bg-emerald-100 text-emerald-700'"
                class="px-2 py-1 rounded text-xs font-semibold"
              >
                {{ m.status }}
              </span>
            </td>
            <td class="px-3 py-2">{{ m.started_at || "—" }}</td>
            <td class="px-3 py-2">{{ m.ended_at || "—" }}</td>
            <td class="px-3 py-2">{{ (m.duration_minutes / 60).toFixed(1) }}時間</td>
            <td class="px-3 py-2">
              <template v-if="m.latitude">
                <a
                  class="text-blue-600 underline"
                  :href="`https://www.google.com/maps?q=${m.latitude},${m.longitude}`"
                  target="_blank"
                >
                  地図で見る
                </a>
              </template>
              <span v-else class="text-gray-400">—</span>
            </td>
            <td class="px-3 py-2 text-right">
              <Link
                :href="route('admin.call-matches.show', { match: m.id })"
                class="text-blue-600 hover:underline text-xs"
                >詳細</Link
              >
            </td>
          </tr>
          <tr v-if="!matches.length">
            <td colspan="9" class="px-3 py-6 text-center text-gray-400">
              コール履歴がありません
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </AdminLayout>
</template>
