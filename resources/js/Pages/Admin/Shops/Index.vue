<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const props = defineProps({
  shops: Object,           // paginate
  filters: Object,         // { q }
  selected: Object,        // { id, name, invites: [] } | null
  captureUrl: String,      // 例 /s/TOKEN_SAMPLE
  members: Object,
})

const shopsData  = computed(() => props.shops?.data ?? [])
const shopsLinks = computed(() => props.shops?.links ?? [])
const q = ref(props.filters?.q ?? '')
const membersData  = computed(() => props.members?.data  ?? [])  // ★
const membersLinks = computed(() => props.members?.links ?? [])  // ★

const menus = [
  { key:'users', label:'ユーザー管理', to:'/admin/users', active:false },
  { key:'casts', label:'キャスト管理', to:'/admin/casts', active:false },
  { key:'shops', label:'ショップ管理', to:'/admin/shops', active:true  },
]

function nav(to){ router.visit(to) }
function search(){ router.get('/admin/shops', { q:q.value }, { preserveState:true, replace:true }) }

/* フォーム */
const selectedId = ref(props.selected?.id ?? null)
const form = useForm({
  id:null, name:'', code:'', contact_email:'', contact_phone:'', is_active:true, note:'',
})
const title = computed(()=> form.id ? 'ショップ編集' : '新規ショップ')

function resetForm(){ form.reset(); form.clearErrors(); form.id=null }
function selectForEdit(s){
  selectedId.value = s.id
  form.id = s.id
  form.name = s.name || ''
  form.code = s.code || ''
  form.contact_email = s.contact_email || ''
  form.contact_phone = s.contact_phone || ''
  form.is_active = !!s.is_active
  form.note = s.note || ''
  router.get('/admin/shops', { q: q.value, selected_id: s.id }, { preserveState: true, replace: true })
 
}
function submitCreate(){ form.post('/admin/shops', { onSuccess:()=> resetForm() }) }
function submitUpdate(){ form.put(`/admin/shops/${form.id}`) }
function removeShop(s){
  if(!confirm('削除しますか？')) return
  router.delete(`/admin/shops/${s.id}`)
}

/* 招待（QR） */
function createInvite(){
  if(!selectedId.value){ alert('先にショップを選択してください'); return }
  router.post(`/admin/shops/${selectedId.value}/invites`, {}, { preserveScroll:true })
}
function qrUrl(invite){ return `/admin/invites/${invite.id}/qr.png` }
function landingUrl(token){
  return props.captureUrl.replace('TOKEN_SAMPLE', token) // /s/{token}
}
function delInvite(id) {
  if (!confirm('削除しますか？')) return
  router.delete(`/admin/invites/${id}`, { preserveScroll: true })
}
</script>

<template>
<AdminLayout active-key="shops">
  <Head title="ショップ管理" />
  <div class="h-screen overflow-hidden bg-gray-50">
    <div class="grid grid-cols-12 h-full">
      <!-- 左メニュー -->
      <aside class="fixed md:static top-0 left-0 h-full md:h-auto w-56 md:w-auto col-span-12 md:col-span-2 border-r bg-white z-50">
        <nav class="px-2 py-4 space-y-1">
          <button v-for="m in menus" :key="m.key" type="button"
                  @click="nav(m.to)"
                  class="w-full text-left px-3 py-2 rounded-lg"
                  :class="m.active ? 'bg-black text-white' : 'hover:bg-gray-100'">
            {{ m.label }}
          </button>
        </nav>
      </aside>

      <!-- 右ペイン -->
      <section id="right-pane" class="col-span-12 md:col-span-10 md:ml-56 flex flex-col">
        <!-- ヘッダ -->
        <div class="px-5 py-3 bg-white border-b flex items-center justify-between">
          <div class="text-xl font-semibold">ショップ管理</div>
          <div class="flex gap-2">
            <input v-model="q" @keyup.enter="search" type="text" class="border rounded px-3 py-2 w-72"
                   placeholder="店舗名 / コード / 連絡先を検索" />
            <button @click="search" class="px-4 py-2 rounded bg-black text-white">検索</button>
            <button @click="resetForm" class="px-3 py-2 rounded bg-gray-100">＋ 新規</button>
          </div>
        </div>

        <!-- 上：ショップ一覧 -->
        <div class="p-4 overflow-auto" style="height: calc(55% - 56px);">
          <div class="bg-white rounded-2xl shadow divide-y">
            <div v-if="shopsData.length===0" class="px-4 py-6 text-sm text-gray-500">ショップがありません</div>

            <div v-for="s in shopsData" :key="s.id"
                 class="px-4 py-3 flex items-center justify-between hover:bg-gray-50"
                 :class="selectedId===s.id ? 'bg-gray-50' : ''">
              <div>
                <div class="font-medium">
                  {{ s.name }} <span class="text-xs text-gray-500 ml-2">{{ s.code }}</span>
                </div>
                <div class="text-xs text-gray-500">
                  {{ s.contact_email || '-' }} ・ {{ s.contact_phone || '-' }}
                  <span class="ml-2" :class="s.is_active ? 'text-green-600' : 'text-gray-400'">
                    {{ s.is_active ? '有効' : '無効' }}
                  </span>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <button @click="selectForEdit(s)" class="text-sm px-2 py-1 rounded bg-blue-600 text-white">編集</button>
                <button @click="removeShop(s)" class="text-sm px-2 py-1 rounded bg-red-600 text-white">削除</button>
              </div>
            </div>
          </div>

          <div class="mt-4 flex gap-2 flex-wrap">
            <Link v-for="(lnk,i) in shopsLinks" :key="i" :href="lnk.url || '#'"
                  class="px-3 py-1 border rounded"
                  :class="[lnk.active ? 'bg-black text-white':'', !lnk.url ? 'opacity-50 pointer-events-none' : '']"
                  v-html="lnk.label" />
          </div>
        </div>

        <!-- 仕切り -->
        <div class="h-2 bg-gray-200"></div>

        <!-- 下：編集 招待(QR)一覧 -->
        <div class="p-4 overflow-auto" style="height: calc(45% - 2px);">
          <div class="grid grid-cols-12 gap-4">
            <!-- フォーム -->
            <div class="col-span-12 lg:col-span-6">
              <div class="bg-white rounded-2xl shadow p-4">
                <h2 class="text-lg font-semibold mb-3">{{ title }}</h2>
                <form @submit.prevent="form.id ? submitUpdate() : submitCreate()" class="grid grid-cols-12 gap-3">
                  <div class="col-span-12 md:col-span-6">
                    <label class="text-sm">店舗名</label>
                    <input v-model="form.name" type="text" class="w-full border rounded px-3 py-2">
                  </div>
                  <div class="col-span-12 md:col-span-6">
                    <label class="text-sm">コード</label>
                    <input v-model="form.code" type="text" class="w-full border rounded px-3 py-2" placeholder="空なら自動生成">
                  </div>
                  <div class="col-span-6">
                    <label class="text-sm">メール</label>
                    <input v-model="form.contact_email" type="email" class="w-full border rounded px-3 py-2">
                  </div>
                  <div class="col-span-6">
                    <label class="text-sm">電話</label>
                    <input v-model="form.contact_phone" type="text" class="w-full border rounded px-3 py-2">
                  </div>
                  <div class="col-span-12 flex items-center gap-2">
                    <input id="active" type="checkbox" v-model="form.is_active" class="h-4 w-4">
                    <label for="active" class="text-sm">有効</label>
                  </div>
                  <div class="col-span-12">
                    <label class="text-sm">メモ</label>
                    <textarea v-model="form.note" rows="3" class="w-full border rounded px-3 py-2"></textarea>
                  </div>

                  <div class="col-span-12 flex gap-2">
                    <button type="submit" class="px-4 py-2 rounded bg-black text-white">
                      {{ form.id ? '更新する' : '作成する' }}
                    </button>
                    <button type="button" @click="resetForm" class="px-4 py-2 rounded bg-gray-100">クリア</button>
                  </div>
                </form>
              </div>
            </div>

            <!-- 招待QR -->
            <div class="col-span-12 lg:col-span-6">
              <div class="bg-white rounded-2xl shadow p-4">
                <div class="flex items-center justify-between mb-3">
                  <h2 class="text-lg font-semibold">招待QR</h2>
                  <button @click="createInvite" class="px-3 py-2 rounded bg-black text-white">＋ 新規発行</button>
                </div>

                <div v-if="!props.selected" class="text-sm text-gray-500">
                  左上でショップを選んでください。
                </div>

                <div v-else class="grid grid-cols-12 gap-3">
                  <div v-for="inv in props.selected.invites" :key="inv.id"
                       class="col-span-6 md:col-span-4 lg:col-span-3 border rounded-xl p-2">
                    <img :src="qrUrl(inv)" class="w-full rounded-lg" alt="QR">
                    <div class="mt-2 text-[10px] break-all text-gray-500">
                      {{ landingUrl(inv.token) }}
                    </div>
                    <div class="mt-1 text-[11px]">
                      使用: {{ inv.used_count }} /
                      <span v-if="inv.max_uses">{{ inv.max_uses }}</span><span v-else>∞</span>
                    </div>
                    <button
                      @click="delInvite(inv.id)"
                      class="w-full text-xs border rounded px-2 py-1 hover:bg-gray-50 mt-2">
                      削除
                    </button>
                  </div>
                </div>

              </div>
              <div class="col-span-12 lg:col-span-6">
                <div class="bg-white rounded-2xl shadow p-4">
                  <div class="flex items-center justify-between mb-3">
                    <h2 class="text-lg font-semibold">メンバー一覧</h2>
                    <span class="text-sm text-gray-500">
                      {{ props.members?.total ?? 0 }} 件
                    </span>
                  </div>
              
                  <div v-if="membersData.length === 0" class="text-sm text-gray-500">
                    このショップのメンバーはいません
                  </div>
              
                  <table v-else class="w-full text-sm">
                    <thead>
                      <tr class="border-b bg-gray-50">
                        <th class="text-left p-2">名前</th>
                        <th class="text-left p-2">メール</th>
                        <th class="text-right p-2 w-28">操作</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="u in membersData" :key="u.id" class="border-b">
                        <td class="p-2">{{ u.name }}</td>
                        <td class="p-2 text-gray-600">{{ u.email }}</td>
                        <td class="p-2 text-right">
                          <Link :href="`/admin/users?q=${encodeURIComponent(u.email)}`" class="text-xs border rounded px-2 py-1">
                            ユーザーを見る
                          </Link>
                        </td>
                      </tr>
                    </tbody>
                  </table>
              
                  <div class="mt-3 flex gap-2 flex-wrap">
                    <Link v-for="(lnk,i) in membersLinks" :key="i" :href="lnk.url || '#'"
                          class="px-3 py-1 border rounded"
                          :class="[lnk.active ? 'bg-black text-white' : '', !lnk.url ? 'opacity-50 pointer-events-none' : '']"
                          v-html="lnk.label" />
                  </div>
                </div>
              </div>
            </div>
           <!-- ★ 右下：メンバー一覧 -->
          </div>
        </div>
      </section>
    </div>
  </div>
</AdminLayout>
</template>
