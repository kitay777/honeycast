<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { computed } from 'vue'
const props = defineProps({ invites: Object })
const rows  = computed(()=> props.invites?.data ?? [])
const links = computed(()=> props.invites?.links ?? [])

function respond(id, ok) {
  const url = ok ? `/cast/invitations/${id}/accept` : `/cast/invitations/${id}/decline`
  router.put(url, {}, { preserveScroll:true })
}
</script>

<template>
  <Head title="出演依頼" />
  <div class="max-w-3xl mx-auto p-4">
    <h1 class="text-xl font-semibold mb-3">出演依頼</h1>

    <div class="bg-white rounded-2xl shadow overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="p-2 text-left">日時</th>
            <th class="p-2 text-left">依頼者</th>
            <th class="p-2 text-left">状態</th>
            <th class="p-2 text-right w-40">操作</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="rows.length===0"><td colspan="4" class="p-4 text-gray-500">依頼はありません</td></tr>
          <tr v-for="a in rows" :key="a.id" class="border-b">
            <td class="p-2">
              {{ a.call_request?.date }} {{ (a.call_request?.start_time||'').slice(0,5) }}–{{ (a.call_request?.end_time||'').slice(0,5) }}
            </td>
            <td class="p-2">
              {{ a.call_request?.user?.name }}
              <div class="text-xs text-gray-500">{{ a.call_request?.user?.email }}</div>
            </td>
            <td class="p-2">
              <span class="px-2 py-0.5 rounded text-xs border">{{ a.status }}</span>
            </td>
            <td class="p-2 text-right">
              <button v-if="a.status==='invited'"
                      @click="respond(a.id, true)"
                      class="text-xs px-3 py-1 rounded bg-black text-white mr-1">参加</button>
              <button v-if="a.status==='invited'"
                      @click="respond(a.id, false)"
                      class="text-xs px-3 py-1 rounded border">辞退</button>
              <span v-else class="text-xs text-gray-500">回答済み</span>
            </td>
          </tr>
        </tbody>
      </table>

      <div class="p-3 flex gap-2 flex-wrap">
        <Link v-for="(lnk,i) in links" :key="i" :href="lnk.url || '#'"
              class="px-3 py-1 border rounded"
              :class="[lnk.active ? 'bg-black text-white' : '', !lnk.url ? 'opacity-50 pointer-events-none' : '']"
              v-html="lnk.label" />
      </div>
    </div>
  </div>
</template>
