<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  month: String,        // 'YYYY-MM'
  casts: Array,         // [{id,label,email}]
  cast_id: Number,      // 選択中 cast_profile_id
  schedules: Object,    // paginate() of cast_shifts
})

/* 安全参照 */
const rows  = computed(() => props.schedules?.data  ?? [])
const links = computed(() => props.schedules?.links ?? [])

const month = ref(props.month || new Date().toISOString().slice(0,7))
const castId = ref(props.cast_id || '')

function reload(){
  router.get('/admin/schedules', { month: month.value, cast_id: castId.value || '' }, { preserveState:true, replace:true })
}
function moveMonth(delta){
  const [y,m] = month.value.split('-').map(n=>parseInt(n,10))
  const d = new Date(y, (m-1)+delta, 1)
  month.value = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}`
  reload()
}

/* 上下スプリット */
const topPct = ref(parseInt(localStorage.getItem('admin_sched_split') || '60', 10))
let dragging=false
function startDrag(e){ dragging=true; document.body.style.cursor='row-resize'; e.preventDefault() }
function onDrag(e){
  if(!dragging) return
  const el=document.getElementById('right-pane'); if(!el) return
  const r=el.getBoundingClientRect()
  const y=Math.min(Math.max(e.clientY-r.top,120), r.height-160)
  topPct.value=Math.min(Math.max(Math.round((y/r.height)*100),25),80)
}
function endDrag(){ if(!dragging) return; dragging=false; document.body.style.cursor='default'; localStorage.setItem('admin_sched_split', String(topPct.value)) }
onMounted(()=>{ window.addEventListener('mousemove', onDrag); window.addEventListener('mouseup', endDrag); window.addEventListener('mouseleave', endDrag) })
onBeforeUnmount(()=>{ window.removeEventListener('mousemove', onDrag); window.removeEventListener('mouseup', endDrag); window.removeEventListener('mouseleave', endDrag) })

/* フォーム（CastShift仕様） */
const selectedId = ref(null)
const form = useForm({
  id: null,
  cast_profile_id: '',
  date: month.value + '-01',
  start_time: '10:00',
  end_time: '12:00',
  is_reserved: false,
})
const titleText = computed(()=> form.id ? 'シフト編集' : '新規シフト')

function resetForm(){ form.reset(); form.clearErrors(); form.id=null; selectedId.value=null; form.date = month.value + '-01' }

function pick(r){
  selectedId.value = r.id
  form.id = r.id
  form.cast_profile_id = r.cast_profile_id
  form.date = r.date
  form.start_time = r.start_time?.slice(0,5) || '00:00'
  form.end_time   = r.end_time?.slice(0,5)   || '00:00'
  form.is_reserved = !!r.is_reserved
}

function submitCreate(){ form.post('/admin/schedules', { onSuccess:()=> resetForm(), preserveScroll:true }) }
function submitUpdate(){ form.put(`/admin/schedules/${form.id}`, { preserveScroll:true }) }
function removeRow(r){
  if(!confirm('削除しますか？')) return
  router.delete(`/admin/schedules/${r.id}`, { onSuccess: ()=> { if(selectedId.value===r.id) resetForm() } })
}
</script>

<template>
  <Head title="スケジュール" />

  <AdminLayout active-key="schedules">
    <!-- ヘッダ -->
    <template #header>
      <div class="px-5 py-3 bg-white border-b flex items-center justify-between">
        <div class="text-xl font-semibold">スケジュール</div>
        <div class="flex items-center gap-2">
          <button class="px-2 py-1 rounded border" @click="moveMonth(-1)">«</button>
          <input type="month" v-model="month" @change="reload" class="border rounded px-3 py-2">
          <button class="px-2 py-1 rounded border" @click="moveMonth(1)">»</button>

          <select v-model="castId" @change="reload" class="border rounded px-3 py-2 ml-2">
            <option value="">全キャスト</option>
            <option v-for="c in props.casts" :key="c.id" :value="c.id">{{ c.label }}</option>
          </select>

          <button @click="resetForm" class="px-3 py-2 rounded bg-gray-100 ml-2">＋ 新規</button>
        </div>
      </div>
    </template>

    <!-- 上：一覧 -->
    <div class="p-4 overflow-auto" :style="{ height: `calc(${topPct}% - 56px)` }">
      <div class="bg-white rounded-2xl shadow">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b bg-gray-50">
              <th class="text-left p-2 w-32">日付</th>
              <th class="text-left p-2 w-28">時間</th>
              <th class="text-left p-2">キャスト</th>
              <th class="text-left p-2 w-28">予約</th>
              <th class="text-right p-2 w-32">操作</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="rows.length===0"><td colspan="5" class="p-4 text-gray-500">この月の予定はありません</td></tr>
            <tr v-for="r in rows" :key="r.id" class="border-b hover:bg-gray-50">
              <td class="p-2">{{ r.date }}</td>
              <td class="p-2">{{ (r.start_time || '').slice(0,5) }}–{{ (r.end_time || '').slice(0,5) }}</td>
              <td class="p-2">
                {{ r.cast_profile?.nickname || r.cast_profile?.user?.name || ('#'+r.cast_profile_id) }}
              </td>
              <td class="p-2">
                <span class="px-2 py-0.5 rounded text-xs border" :class="r.is_reserved ? 'bg-gray-900 text-white' : ''">
                  {{ r.is_reserved ? '予約済' : '空き' }}
                </span>
              </td>
              <td class="p-2 text-right">
                <button @click="pick(r)" class="text-xs px-2 py-1 rounded bg-blue-600 text-white">編集</button>
                <button @click="removeRow(r)" class="text-xs px-2 py-1 rounded bg-red-600 text-white ml-1">削除</button>
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

    <!-- 下：フォーム -->
    <div class="p-4 overflow-auto" :style="{ height: `calc(${100 - topPct}% - 2px)` }">
      <div class="bg-white rounded-2xl shadow p-4">
        <h2 class="text-lg font-semibold mb-3">{{ titleText }}</h2>

        <form @submit.prevent="form.id ? submitUpdate() : submitCreate()" class="grid grid-cols-12 gap-3">
          <div class="col-span-12 md:col-span-4">
            <label class="text-sm">キャスト</label>
            <select v-model="form.cast_profile_id" class="w-full border rounded px-3 py-2">
              <option value="">選択してください</option>
              <option v-for="c in props.casts" :key="c.id" :value="c.id">{{ c.label }}</option>
            </select>
            <div v-if="form.errors.cast_profile_id" class="text-xs text-red-600 mt-1">{{ form.errors.cast_profile_id }}</div>
          </div>

          <div class="col-span-6 md:col-span-2">
            <label class="text-sm">日付</label>
            <input v-model="form.date" type="date" class="w-full border rounded px-3 py-2">
            <div v-if="form.errors.date" class="text-xs text-red-600 mt-1">{{ form.errors.date }}</div>
          </div>

          <div class="col-span-6 md:col-span-2">
            <label class="text-sm">開始</label>
            <input v-model="form.start_time" type="time" class="w-full border rounded px-3 py-2">
            <div v-if="form.errors.start_time" class="text-xs text-red-600 mt-1">{{ form.errors.start_time }}</div>
          </div>

          <div class="col-span-6 md:col-span-2">
            <label class="text-sm">終了（翌日可・最大34:00）</label>
            <input
              v-model="form.end_time"
              type="text"
              placeholder="10:00〜34:00"
              class="w-full border rounded px-3 py-2"
            />
            <div v-if="form.errors.end_time" class="text-xs text-red-600 mt-1">{{ form.errors.end_time }}</div>
          </div>

          <div class="col-span-6 md:col-span-2 flex items-center gap-2">
            <input id="reserved" type="checkbox" v-model="form.is_reserved" class="h-4 w-4">
            <label for="reserved" class="text-sm">予約済み</label>
          </div>

          <div class="col-span-12 flex gap-2 pt-2">
            <button type="submit" class="px-4 py-2 rounded bg-black text-white disabled:opacity-50"
                    :disabled="form.processing">
              {{ form.id ? '更新する' : '作成する' }}
            </button>
            <button type="button" @click="resetForm" class="px-4 py-2 rounded bg-gray-100">クリア</button>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>
