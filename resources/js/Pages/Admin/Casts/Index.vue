<script setup>
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3'
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

/** route() フォールバック */
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

/* 一覧・ページネーション */
const castsData  = computed(() => props.casts?.data  ?? [])
const castsLinks = computed(() => props.casts?.links ?? [])

/* 検索 */
const q = ref(props.filters?.q ?? '')
function search() {
  router.get('/admin/casts', { q: q.value }, { preserveState: true, replace: true })
}

/* 上下スプリット（従来のマウスイベント版：必要なら Pointer 版に差替OK） */
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

/* 選択＆編集フォーム */
const selectedId = ref(null)
const form = useForm({
  id: null,
  // User（新規時 firstOrCreate 用）
  name: '', email: '',
  // CastProfile
  nickname: '', rank: '', age: null, height_cm: null,
  cup: '', style: '', alcohol: '', mbti: '',
  area: '', tags: '', freeword: '',
  photo: null,
})
const title = computed(() => form.id ? 'キャスト編集' : '新規キャスト')

function resetForm(){ form.reset(); form.clearErrors(); form.id=null; selectedId.value=null; resetPointsPanel() }
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

  // ★ 紐づくユーザーのポイント読込
  const uid = c?.user?.id
  loadPoints(uid)
}
function submitCreate(){ form.post('/admin/casts', { forceFormData:true, onSuccess: () => resetForm() }) }
function submitUpdate(){ form.post(`/admin/casts/${form.id}`, { method:'put', forceFormData:true }) }
function remove(c){ if(!confirm('削除しますか？')) return
  router.delete(`/admin/casts/${c.id}`, { onSuccess: ()=> { if(selectedId.value===c.id) resetForm() } })
}

/* ───────── LINE 送信（キャストの紐づくユーザーへ） ───────── */
const lineForm = useForm({ text:'', notification_disabled:false })
const sendingLine = ref(false)
function openLineAndScroll(c){
  selectForEdit(c)
  setTimeout(()=>{ document.getElementById('line-send-box')?.scrollIntoView({ behavior:'smooth', block:'start' }) }, 0)
}
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

/* ───────── ポイント（紐づくユーザー：残高＋履歴＋調整） ───────── */
const loadingPoints = ref(false)
const points = ref({ balance: 0, history: [] })
const pointsForm = useForm({ delta: 0, reason: '' })
const pointsError = ref('')
const currentUserId = ref(null) // 紐づくユーザーID（選択キャストの）

function resetPointsPanel(){
  points.value = { balance: 0, history: [] }
  pointsForm.reset('delta', 'reason')
  pointsError.value = ''
  loadingPoints.value = false
  currentUserId.value = null
}

async function loadPoints(userId){
  resetPointsPanel()
  if (!userId) return
  currentUserId.value = userId
  loadingPoints.value = true
  try {
    const res = await fetch(`/admin/users/${userId}/points`, { headers: { 'Accept':'application/json' } })
    if (!res.ok) throw new Error(`HTTP ${res.status}`)
    points.value = await res.json()
  } catch(e){
    pointsError.value = '履歴の読み込みに失敗しました。'
    console.error(e)
  } finally {
    loadingPoints.value = false
  }
}

async function submitPoints(){
  if (!currentUserId.value) return
  pointsError.value = ''
  await pointsForm.post(`/admin/users/${currentUserId.value}/points/adjust`, {
    preserveScroll: true,
    onSuccess: () => { pointsForm.reset('delta','reason'); loadPoints(currentUserId.value) },
    onError: (errs) => { pointsError.value = errs?.delta || errs?.reason || '調整に失敗しました。' },
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

    <!-- 下：編集フォーム + LINE送信 + ポイント -->
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

      <!-- ───────── ポイント（紐づくユーザー：残高＋履歴＋調整） ───────── -->
      <div class="bg-white rounded-2xl shadow p-4 mt-4">
        <h3 class="text-lg font-semibold mb-2">ポイント（紐づくユーザー）</h3>

        <div v-if="!castsData.find(v => v.id === selectedId)?.user?.id" class="text-sm text-gray-500">
          紐づくユーザーがありません。上の一覧からキャストを選択してください。
        </div>

        <template v-else>
          <div class="flex flex-wrap items-end gap-4">
            <div class="text-sm">
              残高：
              <span class="text-2xl font-bold">
                {{ loadingPoints ? '…' : (points.balance || 0).toLocaleString() }}
              </span> pt
            </div>

            <form @submit.prevent="submitPoints" class="flex items-end gap-2">
              <div>
                <label class="text-sm">増減（例 +100 / -50）</label>
                <input v-model.number="pointsForm.delta" type="number" class="border rounded px-3 py-2 w-28" />
              </div>
              <div>
                <label class="text-sm">理由（任意）</label>
                <input v-model="pointsForm.reason" type="text" class="border rounded px-3 py-2 w-64" />
              </div>
              <button :disabled="pointsForm.processing || !castsData.find(v => v.id === selectedId)?.user?.id"
                      class="px-3 py-2 rounded bg-emerald-600 text-white disabled:opacity-60">
                反映
              </button>
            </form>
          </div>

          <div class="mt-2 text-xs text-red-600" v-if="pointsError">{{ pointsError }}</div>

          <div class="mt-3">
            <div class="text-sm font-semibold mb-1">履歴（最新20件）</div>
            <div v-if="loadingPoints" class="text-gray-500 text-sm">読み込み中…</div>
            <div v-else-if="!points.history.length" class="text-gray-500 text-sm">履歴はまだありません。</div>
            <ul v-else class="divide-y">
              <li v-for="h in points.history" :key="h.id" class="py-2 flex items-center justify-between text-sm">
                <div :class="h.delta >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                  {{ h.delta >= 0 ? '+' : '' }}{{ h.delta }} pt
                  <span class="text-gray-500 ml-2">{{ h.reason || '—' }}</span>
                </div>
                <div class="text-right text-gray-500">
                  <div>{{ h.created_at }}</div>
                  <div class="text-xs">残高: {{ h.after }}</div>
                </div>
              </li>
            </ul>
          </div>
        </template>
      </div>

      <!-- ───────── LINEメッセージ送信 ───────── -->
      <div id="line-send-box" class="bg-white rounded-2xl shadow p-4 mt-4">
        <h3 class="text-lg font-semibold mb-2">LINE メッセージ送信</h3>
        <div class="text-sm text-gray-600 mb-2">
          送信先ユーザーID:
          <span class="font-medium">
            {{ castsData.find(v => v.id === selectedId)?.user?.id ?? '-' }}
          </span>
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
