<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

/**
 * props:
 *  - tags: Array または paginate() の戻り（{ data, links }）
 *  - filters: { q?: string }  検索キーワード
 */
const props = defineProps({
  tags: [Array, Object],
  filters: Object
})

/* データ（paginate/配列の両対応） */
const tagsData  = computed(() => Array.isArray(props.tags) ? props.tags : (props.tags?.data ?? []))
const tagsLinks = computed(() => Array.isArray(props.tags) ? [] : (props.tags?.links ?? []))

/* 検索 */
const q = ref(props.filters?.q ?? '')
const go = (url, params={}, options={}) => {
  // Ziggy が無くても動く fallback
  if (typeof route === 'function') return router.get(route(url, params), {}, options)
  return router.get(url + (Object.keys(params).length ? ('?' + new URLSearchParams(params)) : ''), {}, options)
}
const search = () => {
  // /admin/tags?q=...
  if (typeof route === 'function') {
    router.get(route('admin.tags.index'), { q: q.value }, { preserveState: true, replace: true })
  } else {
    router.get('/admin/tags', { q: q.value }, { preserveState: true, replace: true })
  }
}

/* 上下スプリット（キャスト管理に合わせる） */
const topPct = ref(parseInt(localStorage.getItem('admin_tags_split') || '55', 10))
let dragging = false
function startDrag(e){ dragging = true; document.body.style.cursor='row-resize'; e.preventDefault() }
function onDrag(e){
  if(!dragging) return
  const el = document.getElementById('right-pane')
  if(!el) return
  const r = el.getBoundingClientRect()
  const y = Math.min(Math.max(e.clientY - r.top, 120), r.height - 160)
  topPct.value = Math.min(Math.max(Math.round((y/r.height)*100), 25), 80)
}
function endDrag(){
  if(!dragging) return
  dragging = false
  document.body.style.cursor='default'
  localStorage.setItem('admin_tags_split', String(topPct.value))
}
onMounted(()=>{ window.addEventListener('mousemove', onDrag); window.addEventListener('mouseup', endDrag); window.addEventListener('mouseleave', endDrag) })
onBeforeUnmount(()=>{ window.removeEventListener('mousemove', onDrag); window.removeEventListener('mouseup', endDrag); window.removeEventListener('mouseleave', endDrag) })

/* 編集フォーム */
const selectedId = ref(null)
const form = useForm({
  id: null,
  name: '',
  slug: '',
  is_active: true,
  sort_order: 0,
})
const title = computed(() => form.id ? 'タグ編集' : '新規タグ')

function resetForm(){ form.reset(); form.clearErrors(); form.id=null; selectedId.value=null }

function selectForEdit(t){
  selectedId.value = t.id
  form.id = t.id
  form.name = t.name ?? ''
  form.slug = t.slug ?? ''
  form.is_active = !!t.is_active
  form.sort_order = Number.isFinite(t.sort_order) ? t.sort_order : 0
}

function submitCreate(){
  const url = (typeof route === 'function') ? route('admin.tags.store') : '/admin/tags'
  form.post(url, { onSuccess: () => resetForm() })
}

function submitUpdate(){
  const url = (typeof route === 'function') ? route('admin.tags.update', form.id) : `/admin/tags/${form.id}`
  form.put(url)
}

function remove(t){
  if(!confirm(`「${t.name}」を削除しますか？`)) return
  const url = (typeof route === 'function') ? route('admin.tags.destroy', t.id) : `/admin/tags/${t.id}`
  router.delete(url, { onSuccess: () => { if(selectedId.value===t.id) resetForm() } })
}

function toggleActive(t){
  // 軽いトグル更新（行内保存）
  const url = (typeof route === 'function') ? route('admin.tags.update', t.id) : `/admin/tags/${t.id}`
  router.put(url, {
    name: t.name, slug: t.slug, is_active: !!t.is_active, sort_order: t.sort_order ?? 0
  }, { preserveState: true })
}

/* 便利：簡易スラグ化（空ならサーバでNULLにしてOK） */
function toSlug(s){
  // ラテン以外は落としやすいので、ここでは簡易的に半角/小文字/スペース→- だけ
  return String(s || '').trim().toLowerCase()
    .normalize('NFKD').replace(/[\u0300-\u036f]/g,'')
    .replace(/[^\w\- ]+/g,'').replace(/\s+/g,'-').replace(/\-+/g,'-')
}
function suggestSlug(){ if (!form.slug) form.slug = toSlug(form.name) }
</script>

<template>
  <Head title="タグ管理" />

  <AdminLayout active-key="tags">
    <!-- ヘッダ -->
    <template #header>
      <div class="px-5 py-3 bg-white border-b flex items-center justify-between">
        <div class="text-xl font-semibold">タグ管理</div>
        <div class="flex gap-2">
          <input v-model="q" @keyup.enter="search" type="text"
                 class="border rounded px-3 py-2 w-72"
                 placeholder="タグ名 / スラッグ を検索" />
          <button @click="search" class="px-4 py-2 rounded bg-black text-white">検索</button>
          <button @click="resetForm" class="px-3 py-2 rounded bg-gray-100">＋ 新規</button>
        </div>
      </div>
    </template>

    <!-- 上：一覧 -->
    <div id="right-pane" class="p-4 overflow-auto" :style="{ height: `calc(${topPct}% - 56px)` }">
      <div class="bg-white rounded-2xl shadow divide-y">
        <div v-if="tagsData.length === 0" class="px-4 py-6 text-sm text-gray-500">
          タグがありません（または読み込み中）
        </div>

        <div v-for="t in tagsData" :key="t.id"
             class="px-4 py-3 grid grid-cols-12 items-center gap-3 hover:bg-gray-50"
             :class="selectedId === t.id ? 'bg-gray-50' : ''">
          <div class="col-span-5">
            <div class="font-medium">
              {{ t.name }}
              <span class="text-xs text-gray-500 ml-2">#{{ t.id }}</span>
            </div>
            <div class="text-xs text-gray-500">slug: {{ t.slug || '—' }}</div>
          </div>
          <div class="col-span-2">
            <div class="text-xs text-gray-500">並び順</div>
            <input v-model.number="t.sort_order" type="number" min="0"
                   class="w-24 border rounded px-2 py-1 text-sm" />
          </div>
          <div class="col-span-2">
            <label class="text-xs text-gray-500 block">有効</label>
            <input type="checkbox" v-model="t.is_active" @change="toggleActive(t)" />
          </div>
          <div class="col-span-3 flex items-center justify-end gap-2">
            <button @click="selectForEdit(t)" class="text-sm px-2 py-1 rounded bg-blue-600 text-white">編集</button>
            <button @click="remove(t)" class="text-sm px-2 py-1 rounded bg-red-600 text-white">削除</button>
          </div>
        </div>
      </div>

      <!-- ページネーション（paginate のときだけ） -->
      <div v-if="tagsLinks.length" class="mt-4 flex gap-2 flex-wrap">
        <Link v-for="(lnk,i) in tagsLinks" :key="i"
              :href="lnk.url || '#'"
              class="px-3 py-1 border rounded"
              :class="[lnk.active ? 'bg-black text-white' : '', !lnk.url ? 'opacity-50 pointer-events-none' : '']"
              v-html="lnk.label" />
      </div>
    </div>

    <!-- 仕切り -->
    <div class="h-2 bg-gray-200 hover:bg-gray-300 cursor-row-resize" @mousedown="startDrag"></div>

    <!-- 下：編集フォーム -->
    <div class="p-4 overflow-auto" :style="{ height: `calc(${100 - topPct}% - 2px)` }">
      <div class="bg-white rounded-2xl shadow p-4">
        <h2 class="text-lg font-semibold mb-3">{{ title }}</h2>

        <form @submit.prevent="form.id ? submitUpdate() : submitCreate()" class="grid grid-cols-12 gap-3">
          <div class="col-span-12 md:col-span-5">
            <label class="text-sm">名前</label>
            <input v-model="form.name" @blur="suggestSlug" type="text" class="w-full border rounded px-3 py-2" />
            <p v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</p>
          </div>
          <div class="col-span-12 md:col-span-5">
            <label class="text-sm">スラッグ（任意）</label>
            <input v-model="form.slug" type="text" class="w-full border rounded px-3 py-2" placeholder="空なら自動的にNULL" />
            <p v-if="form.errors.slug" class="text-xs text-red-600 mt-1">{{ form.errors.slug }}</p>
          </div>
          <div class="col-span-6 md:col-span-1">
            <label class="text-sm">順序</label>
            <input v-model.number="form.sort_order" type="number" min="0" class="w-full border rounded px-3 py-2" />
          </div>
          <div class="col-span-6 md:col-span-1 flex items-end">
            <label class="text-sm mr-2">有効</label>
            <input type="checkbox" v-model="form.is_active" />
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
