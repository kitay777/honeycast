<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  requests: Object,      // paginate()
  selected: Object,      // 選択中の詳細( assignments含む ) | null
  candidates: Array,     // 割当候補(空きシフトから推定)
  casts: Array,          // 全キャスト簡易リスト
  filters: Object,       // {status, date, selected_id}
})

const rows  = computed(()=> props.requests?.data  ?? [])
const links = computed(()=> props.requests?.links ?? [])

const status = ref(props.filters?.status ?? '')
const date   = ref(props.filters?.date ?? '')
const selId  = ref(props.filters?.selected_id ?? '')

function reload(extra={}) {
  router.get('/admin/requests',
    { status: status.value, date: date.value, selected_id: selId.value, ...extra },
    { preserveState: true, replace: true })
}
function pickRow(id){ selId.value = id; reload() }

/* 上下スプリット */
const topPct = ref(parseInt(localStorage.getItem('admin_req_split') || '60', 10))
let dragging=false
function startDrag(e){ dragging=true; document.body.style.cursor='row-resize'; e.preventDefault() }
function onDrag(e){ if(!dragging) return; const el=document.getElementById('right-pane'); if(!el) return
  const r=el.getBoundingClientRect(); const y=Math.min(Math.max(e.clientY-r.top,120), r.height-160)
  topPct.value=Math.min(Math.max(Math.round((y/r.height)*100),25),80)
}
function endDrag(){ if(!dragging) return; dragging=false; document.body.style.cursor='default'; localStorage.setItem('admin_req_split', String(topPct.value)) }
onMounted(()=>{ window.addEventListener('mousemove', onDrag); window.addEventListener('mouseup', endDrag); window.addEventListener('mouseleave', endDrag) })
onBeforeUnmount(()=>{ window.removeEventListener('mousemove', onDrag); window.removeEventListener('mouseup', endDrag); window.removeEventListener('mouseleave', endDrag) })

/* 割当フォーム */
const form = useForm({ cast_profile_id:'', note:'' })
function assign(reqId){
  if(!form.cast_profile_id){ alert('キャストを選んでください'); return }
  form.post(`/admin/requests/${reqId}/assign`, { preserveScroll:true, onSuccess:()=> { form.reset() } })
}
function unassign(reqId, assignmentId){
  if(!confirm('解除しますか？')) return
  router.delete(`/admin/requests/${reqId}/assign/${assignmentId}`, { preserveScroll:true })
}
function updateStatus(reqId, newStatus){
  router.put(`/admin/requests/${reqId}/status`, { status:newStatus }, { preserveScroll:true })
}
</script>

<template>
  <Head title="リクエスト" />

  <AdminLayout active-key="requests">
    <!-- ヘッダ -->
    <template #header>
      <div class="px-5 py-3 bg-white border-b flex items-center justify-between">
        <div class="text-xl font-semibold">コールリクエスト</div>
        <div class="flex items-center gap-2">
          <select v-model="status" @change="reload()" class="border rounded px-3 py-2">
            <option value="">全ステータス</option>
            <option value="pending">pending</option>
            <option value="assigned">assigned</option>
            <option value="closed">closed</option>
          </select>
          <input type="month" v-model="date" @change="reload()" class="border rounded px-3 py-2">
        </div>
      </div>
    </template>

    <!-- 上：リスト -->
    <div class="p-4 overflow-auto" :style="{height: `calc(${topPct}% - 56px)`}">
      <div class="bg-white rounded-2xl shadow">
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-gray-50 border-b">
              <th class="text-left p-2 w-16">ID</th>
              <th class="text-left p-2">依頼者</th>
              <th class="text-left p-2 w-36">日付</th>
              <th class="text-left p-2 w-28">時間</th>
              <th class="text-left p-2 w-20">人数</th>
              <th class="text-left p-2">場所</th>
              <th class="text-left p-2 w-24">ステータス</th>
              <th class="text-right p-2 w-28">操作</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="rows.length===0"><td colspan="8" class="p-4 text-gray-500">リクエストがありません</td></tr>
            <tr v-for="r in rows" :key="r.id" class="border-b hover:bg-gray-50">
              <td class="p-2">#{{ r.id }}</td>
              <td class="p-2">{{ r.user?.name }} <span class="text-xs text-gray-500">{{ r.user?.email }}</span></td>
              <td class="p-2">{{ r.date }}</td>
              <td class="p-2">{{ (r.start_time||'').slice(0,5) }}–{{ (r.end_time||'').slice(0,5) }}</td>
              <td class="p-2">{{ r.cast_count ?? '-' }}</td>
              <td class="p-2">{{ r.place || '-' }}</td>
              <td class="p-2">
                <span class="px-2 py-0.5 rounded text-xs border">{{ r.status }}</span>
              </td>
              <td class="p-2 text-right">
                <button @click="pickRow(r.id)" class="text-xs px-2 py-1 rounded bg-blue-600 text-white">開く</button>
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

    <!-- 仕切り -->
    <div class="h-2 bg-gray-200 hover:bg-gray-300 cursor-row-resize" @mousedown="startDrag"></div>

    <!-- 下：詳細 & 割当 -->
    <div class="p-4 overflow-auto" :style="{height: `calc(${100 - topPct}% - 2px)`}">
      <div class="bg-white rounded-2xl shadow p-4" v-if="props.selected">
        <div class="flex items-start justify-between mb-3">
          <div>
            <div class="text-lg font-semibold">リクエスト #{{ props.selected.id }}</div>
            <div class="text-sm text-gray-600">
              {{ props.selected.date }} {{ (props.selected.start_time||'').slice(0,5) }}–{{ (props.selected.end_time||'').slice(0,5) }}
              ・場所: {{ props.selected.place || '-' }}
              ・希望人数: {{ props.selected.cast_count ?? '-' }}
            </div>
          </div>
          <div class="flex gap-2">
            <button @click="updateStatus(props.selected.id, 'pending')"  class="px-2 py-1 text-xs border rounded">pending</button>
            <button @click="updateStatus(props.selected.id, 'assigned')" class="px-2 py-1 text-xs border rounded">assigned</button>
            <button @click="updateStatus(props.selected.id, 'closed')"   class="px-2 py-1 text-xs border rounded">closed</button>
          </div>
        </div>

        <!-- 現在の割当 -->
        <div class="mb-3">
          <div class="font-medium mb-1">割当済み</div>
          <div v-if="(props.selected.assignments||[]).length===0" class="text-sm text-gray-500">まだ割当はありません</div>
          <ul class="divide-y rounded border">
            <li v-for="a in props.selected.assignments" :key="a.id" class="px-3 py-2 flex items-center justify-between">
              <div>
                {{ a.cast_profile?.nickname || a.cast_profile?.user?.name || ('#'+a.cast_profile_id) }}
                <span class="text-xs ml-2">status: {{ a.status }} <span v-if="a.responded_at">/ {{ new Date(a.responded_at).toLocaleString() }}</span></span>
              </div>
              <button @click="unassign(props.selected.id, a.id)" class="text-xs px-2 py-1 rounded bg-red-600 text-white">解除</button>
            </li>
          </ul>
        </div>

        <!-- 候補（空きシフトから） -->
        <div class="mb-3">
          <div class="font-medium mb-1">候補（空きシフト）</div>
          <div class="flex flex-wrap gap-2">
            <button v-for="c in (props.candidates||[])" :key="c.id"
                    @click="form.cast_profile_id = c.id; form.note=''; assign(props.selected.id)"
                    class="px-2 py-1 text-xs rounded border hover:bg-gray-50">
              {{ c.label }}
            </button>
            <div v-if="(props.candidates||[]).length===0" class="text-sm text-gray-500">該当なし</div>
          </div>
        </div>

        <!-- 手動割当 -->
        <div class="grid grid-cols-12 gap-3">
          <div class="col-span-12 md:col-span-6">
            <label class="text-sm">キャストを検索/選択</label>
            <select v-model="form.cast_profile_id" class="w-full border rounded px-3 py-2">
              <option value="">選択してください</option>
              <option v-for="c in props.casts" :key="c.id" :value="c.id">
                {{ c.label }} <span v-if="c.email" class="text-gray-400">（{{ c.email }}）</span>
              </option>
            </select>
          </div>
          <div class="col-span-12 md:col-span-6">
            <label class="text-sm">メモ</label>
            <input v-model="form.note" type="text" class="w-full border rounded px-3 py-2" placeholder="任意">
          </div>
          <div class="col-span-12">
            <button @click="assign(props.selected.id)" class="px-4 py-2 rounded bg-black text-white">割り振る</button>
          </div>
        </div>
      </div>

      <div v-else class="bg-white rounded-2xl shadow p-6 text-sm text-gray-500">
        上の一覧からリクエストを選択してください。
      </div>
    </div>
  </AdminLayout>
</template>
