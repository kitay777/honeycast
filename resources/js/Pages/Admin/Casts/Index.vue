<script setup>
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3'
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue' // @ が無ければ相対パスに

/** route() が無い/解決失敗でもフォールバック */
const urlFor = (name, params = {}, fallback = "") => {
  try { if (typeof route === "function") { const u = route(name, params); if (typeof u === "string" && u.length) return u } } catch {}
  return fallback
}

const props = defineProps({
  casts: Object,   // paginate() 戻り
  filters: Object, // { q }
})

const page  = usePage()
const flash = computed(() => page.props?.value?.flash ?? {})

/* データ（未定義ガード） */
const castsData  = computed(() => props.casts?.data  ?? [])
const castsLinks = computed(() => props.casts?.links ?? [])

/* 検索 */
const q = ref(props.filters?.q ?? '')
function search() {
  router.get('/admin/casts', { q: q.value }, { preserveState: true, replace: true })
}

/* 上下スプリット（ドラッグ） */
const topPct = ref(parseInt(localStorage.getItem('admin_casts_split') || '55', 10))
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
  document.body.style.cursor = 'default'
  localStorage.setItem('admin_casts_split', String(topPct.value))
}
onMounted(()=>{ window.addEventListener('mousemove', onDrag); window.addEventListener('mouseup', endDrag); window.addEventListener('mouseleave', endDrag) })
onBeforeUnmount(()=>{ window.removeEventListener('mousemove', onDrag); window.removeEventListener('mouseup', endDrag); window.removeEventListener('mouseleave', endDrag) })

/* フォーム（実スキーマ対応） */
const selectedId = ref(null)
const form = useForm({
  id: null,
  // User（新規時の firstOrCreate 用）
  name: '', email: '',
  // CastProfile
  nickname: '', rank: '', age: null, height_cm: null,
  cup: '', style: '', alcohol: '', mbti: '',
  area: '', tags: '', freeword: '',
  photo: null,
})
const title = computed(() => form.id ? 'キャスト編集' : '新規キャスト')

function resetForm(){ form.reset(); form.clearErrors(); form.id=null; selectedId.value=null }
function selectForEdit(c){
  selectedId.value = c.id
  form.id = c.id
  form.name  = c.user?.name  || ''
  form.email = c.user?.email || ''
  form.nickname  = c.nickname ?? ''
  form.rank      = c.rank ?? ''
  form.age       = c.age ?? null
  form.height_cm = c.height_cm ?? null
  form.cup       = c.cup ?? ''
  form.style     = c.style ?? ''
  form.alcohol   = c.alcohol ?? ''
  form.mbti      = c.mbti ?? ''
  form.area      = c.area ?? ''
  form.tags      = c.tags ?? ''
  form.freeword  = c.freeword ?? ''
  form.photo     = null
}
function submitCreate(){
  form.post('/admin/casts', { forceFormData:true, onSuccess: () => resetForm() })
}
function submitUpdate(){
  form.post(`/admin/casts/${form.id}`, { method:'put', forceFormData:true })
}
function remove(c){
  if(!confirm('削除しますか？')) return
  router.delete(`/admin/casts/${c.id}`, { onSuccess: ()=> { if(selectedId.value===c.id) resetForm() } })
}

/* ───────── LINE 送信（キャストの紐づくユーザーへ） ───────── */
const lineForm = useForm({ text:'', notification_disabled:false })
const sendingLine = ref(false)

/** キャスト行の「LINE送信」→ 下パネルへスクロール＆選択 */
function openLineAndScroll(c){
  selectForEdit(c)
  setTimeout(()=>{ document.getElementById('line-send-box')?.scrollIntoView({ behavior:'smooth', block:'start' }) }, 0)
}

/** 送信：/admin/users/{user}/line/push へPOST（AdminLineController@push） */
async function sendLine(castUserId) {
  if (!castUserId || !lineForm.text) return
  if (sendingLine.value) return
  sendingLine.value = true
  const url = urlFor('admin.users.line.push', { user: castUserId }, `/admin/users/${castUserId}/line/push`)
  await lineForm.post(url, {
    preserveScroll: true,
    onFinish: () => { sendingLine.value = false },
    onSuccess: () => { lineForm.reset('text') },
  })
}
</script>

<template>
  <Head title="キャスト管理" />

  <AdminLayout active-key="casts">
    <!-- ヘッダ -->
    <template #header>
      <div class="px-5 py-3 bg-white border-b flex items-center justify-between">
        <div class="text-xl font-semibold">キャスト管理</div>
        <div class="flex gap-2">
          <input v-model="q" @keyup.enter="search" type="text"
                 class="border rounded px-3 py-2 w-72"
                 placeholder="ニックネーム / ユーザー名 / メール / エリアを検索" />
          <button @click="search" class="px-4 py-2 rounded bg-black text-white">検索</button>
          <button @click="resetForm" class="px-3 py-2 rounded bg-gray-100">＋ 新規</button>
        </div>
      </div>
      <div v-if="flash?.success" class="px-5 py-2 bg-emerald-50 text-emerald-700 border-b">{{ flash.success }}</div>
      <div v-if="flash?.error"   class="px-5 py-2 bg-red-50     text-red-700     border-b whitespace-pre-line">{{ flash.error }}</div>
    </template>

    <!-- 上：一覧 -->
    <div id="right-pane" class="p-4 overflow-auto" :style="{ height: `calc(${topPct}% - 56px)` }">
      <div class="bg-white rounded-2xl shadow divide-y">
        <div v-if="castsData.length === 0" class="px-4 py-6 text-sm text-gray-500">
          キャストがいません（または読み込み中）
        </div>

        <div v-for="c in castsData" :key="c.id"
             class="px-4 py-3 flex items-center justify-between hover:bg-gray-50"
             :class="selectedId === c.id ? 'bg-gray-50' : ''">
          <div class="flex items-center gap-3">
            <img v-if="c.photo_path" :src="`/storage/${c.photo_path}`" class="w-12 h-12 object-cover rounded-xl" />
            <div>
              <div class="font-medium">
                {{ c.nickname || c.user?.name || '(無名)' }}
                <span class="text-xs text-gray-500 ml-2">#{{ c.id }}</span>
              </div>
              <div class="text-xs text-gray-500">
                {{ c.user?.email || '-' }} ・ {{ c.area || '-' }} ・ {{ c.rank || '-' }}
                <span v-if="c.user?.line_user_id" class="ml-2 text-emerald-700">/ LINE連携: 済</span>
                <span v-else class="ml-2 text-gray-400">/ LINE連携: 未</span>
              </div>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <Link :href="`/admin/users?q=${encodeURIComponent(c.user?.email || '')}`"
                  class="text-xs px-2 py-1 rounded border">ユーザーを見る</Link>
            <button @click="selectForEdit(c)" class="text-sm px-2 py-1 rounded bg-blue-600 text-white">編集</button>
            <button @click="openLineAndScroll(c)" class="text-sm px-2 py-1 rounded bg-emerald-600 text-white">LINE送信</button>
            <button @click="remove(c)" class="text-sm px-2 py-1 rounded bg-red-600 text-white">削除</button>
          </div>
        </div>
      </div>

      <!-- ページネーション -->
      <div class="mt-4 flex gap-2 flex-wrap">
        <Link v-for="(lnk,i) in castsLinks" :key="i"
              :href="lnk.url || '#'"
              class="px-3 py-1 border rounded"
              :class="[lnk.active ? 'bg-black text-white' : '', !lnk.url ? 'opacity-50 pointer-events-none' : '']"
              v-html="lnk.label" />
      </div>
    </div>

    <!-- 仕切り -->
    <div class="h-2 bg-gray-200 hover:bg-gray-300 cursor-row-resize" @mousedown="startDrag"></div>

    <!-- 下：編集フォーム + LINE送信 -->
    <div class="p-4 overflow-auto" :style="{ height: `calc(${100 - topPct}% - 2px)` }">
      <div class="bg-white rounded-2xl shadow p-4">
        <h2 class="text-lg font-semibold mb-3">{{ title }}</h2>

        <form @submit.prevent="form.id ? submitUpdate() : submitCreate()" class="grid grid-cols-12 gap-3">
          <!-- User -->
          <div class="col-span-12 md:col-span-4">
            <label class="text-sm">ユーザー名</label>
            <input v-model="form.name" type="text" class="w-full border rounded px-3 py-2" placeholder="任意（新規時）" />
          </div>
          <div class="col-span-12 md:col-span-4">
            <label class="text-sm">メール</label>
            <input v-model="form.email" type="email" class="w-full border rounded px-3 py-2" placeholder="firstOrCreate用" />
          </div>

          <!-- CastProfile -->
          <div class="col-span-6 md:col-span-2">
            <label class="text-sm">ニックネーム</label>
            <input v-model="form.nickname" type="text" class="w-full border rounded px-3 py-2" />
          </div>
          <div class="col-span-6 md:col-span-2">
            <label class="text-sm">ランク</label>
            <input v-model="form.rank" type="text" class="w-full border rounded px-3 py-2" />
          </div>
          <div class="col-span-6 md:col-span-2">
            <label class="text-sm">年齢</label>
            <input v-model.number="form.age" type="number" min="0" class="w-full border rounded px-3 py-2" />
          </div>
          <div class="col-span-6 md:col-span-2">
            <label class="text-sm">身長 (cm)</label>
            <input v-model.number="form.height_cm" type="number" min="0" class="w-full border rounded px-3 py-2" />
          </div>
          <div class="col-span-6 md:col-span-2">
            <label class="text-sm">Cup</label>
            <input v-model="form.cup" type="text" class="w-full border rounded px-3 py-2" />
          </div>
          <div class="col-span-6 md:col-span-2">
            <label class="text-sm">Style</label>
            <input v-model="form.style" type="text" class="w-full border rounded px-3 py-2" />
          </div>

          <div class="col-span-6 md:col-span-2">
            <label class="text-sm">お酒</label>
            <input v-model="form.alcohol" type="text" class="w-full border rounded px-3 py-2" />
          </div>
          <div class="col-span-6 md:col-span-2">
            <label class="text-sm">MBTI</label>
            <input v-model="form.mbti" type="text" class="w-full border rounded px-3 py-2" placeholder="ENFP 等" />
          </div>
          <div class="col-span-6 md:col-span-4">
            <label class="text-sm">エリア</label>
            <input v-model="form.area" type="text" class="w-full border rounded px-3 py-2" />
          </div>

          <div class="col-span-12">
            <label class="text-sm">タグ（カンマ区切り）</label>
            <input v-model="form.tags" type="text" class="w-full border rounded px-3 py-2" />
          </div>
          <div class="col-span-12">
            <label class="text-sm">フリーワード</label>
            <textarea v-model="form.freeword" rows="3" class="w-full border rounded px-3 py-2"></textarea>
          </div>

          <div class="col-span-12">
            <label class="text-sm">写真</label>
            <input type="file" @change="e => form.photo = e.target.files?.[0] ?? null" />
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

      <!-- ───────── LINEメッセージ送信 ───────── -->
      <div id="line-send-box" class="bg-white rounded-2xl shadow p-4 mt-4">
        <h3 class="text-lg font-semibold mb-2">LINE メッセージ送信</h3>
        <div class="text-sm text-gray-600 mb-2">
          送信先ユーザーID: <span class="font-medium">{{ form.id ? '(キャストに紐づくユーザー)' : '-' }}</span>
          <span class="ml-2 text-gray-500">(上の一覧からキャストを選択してから送信)</span>
        </div>
        <div class="space-y-2">
          <label class="block text-sm">メッセージ</label>
          <textarea v-model="lineForm.text" rows="4" class="w-full border rounded px-3 py-2"
                    placeholder="送信内容（最大1000文字）"></textarea>

          <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" v-model="lineForm.notification_disabled">
            通知を鳴らさない
          </label>

          <div class="mt-1 flex items-center gap-2">
            <!-- castsData から選択済みキャストの user.id を引く -->
            <button
              type="button"
              @click="sendLine(castsData.find(v => v.id === selectedId)?.user?.id)"
              :disabled="sendingLine || !selectedId || !lineForm.text"
              class="px-4 py-2 rounded text-white"
              :class="(sendingLine || !selectedId || !lineForm.text) ? 'bg-gray-400' : 'bg-emerald-600 hover:brightness-110'">
              送信
            </button>
            <span class="text-xs text-gray-500">※ 紐づくユーザーが未連携の場合はエラーになります</span>
          </div>
        </div>
      </div>
      <!-- ─────────────────────────────── -->
    </div>
  </AdminLayout>
</template>
