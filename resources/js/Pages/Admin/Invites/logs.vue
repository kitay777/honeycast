<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({ logs:Object, shops:Array, filter:Object })
const rows  = computed(()=> props.logs?.data ?? [])
const links = computed(()=> props.logs?.links ?? [])
const shopId = ref(props.filter?.shop_id ?? '')

function reload(){ router.get('/admin/invite-logs', { shop_id: shopId.value || '' }, { preserveState:true, replace:true }) }
</script>

<template>
  <Head title="招待利用ログ" />
  <AdminLayout active-key="shops">
    <template #header>
      <div class="px-5 py-3 bg-white border-b flex items-center justify-between">
        <div class="text-xl font-semibold">招待利用ログ</div>
        <div class="flex gap-2">
          <select v-model="shopId" @change="reload" class="border rounded px-3 py-2">
            <option value="">全ショップ</option>
            <option v-for="s in shops" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
        </div>
      </div>
    </template>

    <div class="p-4 overflow-auto">
      <div class="bg-white rounded-2xl shadow">
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-gray-50 border-b">
              <th class="text-left p-2 w-20">ID</th>
              <th class="text-left p-2">ユーザー</th>
              <th class="text-left p-2">ショップ</th>
              <th class="text-left p-2">日時</th>
              <th class="text-left p-2">IP</th>
              <th class="text-left p-2">UA</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="rows.length===0"><td colspan="6" class="p-4 text-gray-500">ログはありません</td></tr>
            <tr v-for="r in rows" :key="r.id" class="border-b">
              <td class="p-2">#{{ r.id }}</td>
              <td class="p-2">{{ r.user?.name }} <span class="text-xs text-gray-500">{{ r.user?.email }}</span></td>
              <td class="p-2">{{ r.invite?.shop?.name }}</td>
              <td class="p-2">{{ new Date(r.created_at).toLocaleString() }}</td>
              <td class="p-2">{{ r.ip || '-' }}</td>
              <td class="p-2 truncate max-w-[420px]">{{ r.user_agent || '-' }}</td>
            </tr>
          </tbody>
        </table>
        <div class="p-3 flex gap-2 flex-wrap">
          <Link v-for="(lnk,i) in links" :key="i" :href="lnk.url || '#'"
                class="px-3 py-1 border rounded"
                :class="[lnk.active ? 'bg-black text-white' : '', !lnk.url ? 'opacity-50 pointer-events-none' : '']"
                v-html="lnk.label"/>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
