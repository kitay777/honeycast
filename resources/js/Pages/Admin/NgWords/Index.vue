<!-- resources/js/Pages/Admin/NgWords/Index.vue -->
<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({ words: Object, filters: Object })
const data  = computed(()=> props.words?.data  ?? [])
const links = computed(()=> props.words?.links ?? [])
const q = ref(props.filters?.q ?? '')

const search = () => {
  if (typeof route === 'function') router.get(route('admin.ng.index'), { q:q.value }, { preserveState:true, replace:true })
  else router.get('/admin/ng-words', { q:q.value }, { preserveState:true, replace:true })
}

const topPct = ref(parseInt(localStorage.getItem('admin_ng_split') || '55', 10))
let dragging=false
const startDrag=e=>{ dragging=true; document.body.style.cursor='row-resize'; e.preventDefault() }
const onDrag=e=>{ if(!dragging)return; const el=document.getElementById('right-pane'); if(!el)return;
  const r=el.getBoundingClientRect(); const y=Math.min(Math.max(e.clientY-r.top,120), r.height-160);
  topPct.value=Math.min(Math.max(Math.round((y/r.height)*100),25),80) }
const endDrag=()=>{ if(!dragging)return; dragging=false; document.body.style.cursor='default'; localStorage.setItem('admin_ng_split', String(topPct.value)) }
onMounted(()=>{ window.addEventListener('mousemove', onDrag); window.addEventListener('mouseup', endDrag); window.addEventListener('mouseleave', endDrag) })
onBeforeUnmount(()=>{ window.removeEventListener('mousemove', onDrag); window.removeEventListener('mouseup', endDrag); window.removeEventListener('mouseleave', endDrag) })

const selectedId = ref(null)
const form = useForm({ id:null, phrase:'', match_type:'contain', severity:'block', is_active:true, replacement:'', note:'' })
const title = computed(()=> form.id ? '禁止ワード編集' : '新規追加')
const resetForm=()=>{ form.reset(); form.clearErrors(); form.id=null; selectedId.value=null }
const selectForEdit = (w)=>{ selectedId.value=w.id; form.id=w.id; form.phrase=w.phrase; form.match_type=w.match_type; form.severity=w.severity; form.is_active=!!w.is_active; form.replacement=w.replacement||''; form.note=w.note||'' }

const submitCreate=()=>{ const url= typeof route==='function'?route('admin.ng.store'):'/admin/ng-words'; form.post(url,{onSuccess:()=>resetForm()})}
const submitUpdate=()=>{ const url= typeof route==='function'?route('admin.ng.update',form.id):`/admin/ng-words/${form.id}`; form.put(url) }
const removeWord = (w)=>{ if(!confirm(`「${w.phrase}」を削除しますか？`)) return; const url= typeof route==='function'?route('admin.ng.destroy',w.id):`/admin/ng-words/${w.id}`; router.delete(url,{onSuccess:()=>{ if(selectedId.value===w.id) resetForm(); }}) }
</script>

<template>
  <Head title="禁止ワード管理" />
  <AdminLayout active-key="ng">
    <template #header>
      <div class="px-5 py-3 bg-white border-b flex items-center justify-between">
        <div class="text-xl font-semibold">禁止ワード管理</div>
        <div class="flex gap-2">
          <input v-model="q" @keyup.enter="search" type="text" class="border rounded px-3 py-2 w-72" placeholder="フレーズ/正規表現を検索" />
          <button @click="search" class="px-4 py-2 rounded bg-black text-white">検索</button>
          <button @click="resetForm" class="px-3 py-2 rounded bg-gray-100">＋ 新規</button>
        </div>
      </div>
    </template>

    <!-- 上：一覧 -->
    <div id="right-pane" class="p-4 overflow-auto" :style="{ height: `calc(${topPct}% - 56px)` }">
      <div class="bg-white rounded-2xl shadow divide-y">
        <div v-if="data.length===0" class="px-4 py-6 text-sm text-gray-500">ワードがありません（または読み込み中）</div>

        <div v-for="w in data" :key="w.id"
             class="px-4 py-3 grid grid-cols-12 items-center gap-3 hover:bg-gray-50"
             :class="selectedId===w.id ? 'bg-gray-50' : ''">
          <div class="col-span-6">
            <div class="font-medium">{{ w.phrase }} <span class="text-xs text-gray-500 ml-2">#{{ w.id }}</span></div>
            <div class="text-xs text-gray-500">type: {{ w.match_type }} / severity: {{ w.severity }}</div>
          </div>
          <div class="col-span-2 text-sm">
            <label class="text-xs text-gray-500 block">有効</label>
            <input type="checkbox" :checked="w.is_active" @change="selectForEdit(w); form.is_active=$event.target.checked; submitUpdate();" />
          </div>
          <div class="col-span-4 flex items-center justify-end gap-2">
            <button @click="selectForEdit(w)" class="text-sm px-2 py-1 rounded bg-blue-600 text-white">編集</button>
            <button @click="removeWord(w)" class="text-sm px-2 py-1 rounded bg-red-600 text-white">削除</button>
          </div>
        </div>
      </div>

      <div class="mt-4 flex gap-2 flex-wrap">
        <Link v-for="(lnk,i) in links" :key="i" :href="lnk.url || '#'" class="px-3 py-1 border rounded"
              :class="[lnk.active ? 'bg-black text-white':'' , !lnk.url ? 'opacity-50 pointer-events-none':'' ]" v-html="lnk.label" />
      </div>
    </div>

    <div class="h-2 bg-gray-200 hover:bg-gray-300 cursor-row-resize" @mousedown="startDrag"></div>

    <!-- 下：編集フォーム -->
    <div class="p-4 overflow-auto" :style="{ height: `calc(${100 - topPct}% - 2px)` }">
      <div class="bg-white rounded-2xl shadow p-4">
        <h2 class="text-lg font-semibold mb-3">{{ title }}</h2>
        <form @submit.prevent="form.id ? submitUpdate() : submitCreate()" class="grid grid-cols-12 gap-3">
          <div class="col-span-12">
            <label class="text-sm">フレーズ</label>
            <input v-model="form.phrase" type="text" class="w-full border rounded px-3 py-2" placeholder="例）出会い系 / (?i)abc.*xyz など" />
            <p v-if="form.errors.phrase" class="text-xs text-red-600 mt-1">{{ form.errors.phrase }}</p>
          </div>
          <div class="col-span-6 md:col-span-3">
            <label class="text-sm">照合方法</label>
            <select v-model="form.match_type" class="w-full border rounded px-3 py-2">
              <option value="contain">含む</option>
              <option value="exact">完全一致</option>
              <option value="regex">正規表現</option>
            </select>
          </div>
          <div class="col-span-6 md:col-span-3">
            <label class="text-sm">措置</label>
            <select v-model="form.severity" class="w-full border rounded px-3 py-2">
              <option value="block">ブロック</option>
              <option value="warn">警告（将来）</option>
              <option value="mask">マスク（将来）</option>
            </select>
          </div>
          <div class="col-span-6 md:col-span-3">
            <label class="text-sm">置換（mask用/任意）</label>
            <input v-model="form.replacement" type="text" class="w-full border rounded px-3 py-2" placeholder="*** など" />
          </div>
          <div class="col-span-6 md:col-span-3">
            <label class="text-sm">有効</label>
            <input type="checkbox" v-model="form.is_active" />
          </div>
          <div class="col-span-12">
            <label class="text-sm">メモ（任意）</label>
            <input v-model="form.note" type="text" class="w-full border rounded px-3 py-2" />
          </div>

          <div class="col-span-12 flex gap-2 pt-2">
            <button type="submit" class="px-4 py-2 rounded bg-black text-white disabled:opacity-50" :disabled="form.processing">
              {{ form.id ? '更新する' : '追加する' }}
            </button>
            <button type="button" @click="resetForm" class="px-4 py-2 rounded bg-gray-100">クリア</button>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>
