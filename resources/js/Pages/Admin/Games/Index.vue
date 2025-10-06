<!-- resources/js/Pages/Admin/Games/Index.vue -->
<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({ games: Object })
const rows  = computed(()=> props.games?.data ?? props.games ?? [])

const form = useForm({
  title: '', description: '', is_published: true, sort_order: 0,
  files: [], posters: []
})

const onFiles = e => form.files = Array.from(e.target.files || [])
const onPosters = e => form.posters = Array.from(e.target.files || [])

const submit = () => form.post('/admin/games', {
  forceFormData: true,
  onSuccess: () => form.reset('title','description','files','posters')
})

const updateRow = (g) => {
  router.put(`/admin/games/${g.id}`, {
    title: g.title, description: g.description ?? '',
    is_published: !!g.is_published, sort_order: g.sort_order ?? 0
  })
}
const toggle = (g) => router.patch(`/admin/games/${g.id}/publish`)
const remove = (g) => { if (confirm('削除しますか？')) router.delete(`/admin/games/${g.id}`) }
</script>

<template>
  <Head title="動画（ゲーム）管理" />

  <AdminLayout active-key="games">
  <div class="p-4 max-w-4xl mx-auto">
    <h1 class="text-xl font-semibold mb-3">動画（ゲーム）管理</h1>

    <!-- 登録フォーム -->
    <div class="bg-white rounded-xl shadow p-4 space-y-3">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="text-sm">タイトル（省略時はファイル名）</label>
          <input v-model="form.title" class="w-full border rounded px-3 py-2" />
        </div>
        <div>
          <label class="text-sm">公開</label>
          <select v-model="form.is_published" class="w-full border rounded px-3 py-2">
            <option :value="true">公開</option>
            <option :value="false">非公開</option>
          </select>
        </div>
        <div>
          <label class="text-sm">並び順</label>
          <input v-model.number="form.sort_order" type="number" class="w-full border rounded px-3 py-2" />
        </div>
        <div class="md:col-span-2">
          <label class="text-sm">説明</label>
          <textarea v-model="form.description" rows="2" class="w-full border rounded px-3 py-2"></textarea>
        </div>
        <div>
          <label class="text-sm">動画ファイル（複数可）</label>
          <input type="file" accept="video/mp4,video/webm,video/quicktime,video/x-m4v" multiple @change="onFiles" />
        </div>
        <div>
          <label class="text-sm">サムネ（動画ごと任意。同じ数だけ選択）</label>
          <input type="file" accept="image/*" multiple @change="onPosters" />
        </div>
      </div>

      <button @click="submit" :disabled="form.processing"
        class="px-4 py-2 rounded bg-black text-white">登録</button>
    </div>

    <!-- 既存一覧 -->
    <div class="mt-6 bg-white rounded-xl shadow divide-y">
      <div v-for="g in rows" :key="g.id" class="p-3 flex gap-3 items-center">
        <img v-if="g.poster_path" :src="`/storage/${g.poster_path}`" class="w-24 h-16 object-cover rounded" />
        <video v-else :src="`/storage/${g.file_path}`" class="w-24 h-16 rounded" muted playsinline />
        <div class="flex-1">
          <input v-model="g.title" class="w-full border rounded px-2 py-1 mb-1" />
          <textarea v-model="g.description" rows="1" class="w-full border rounded px-2 py-1 text-sm"></textarea>
          <div class="text-xs text-gray-500 mt-1">
            /games/{{ g.slug }} ・ {{ g.is_published ? '公開' : '非公開' }} ・ sort: {{ g.sort_order }}
          </div>
        </div>
        <div class="flex flex-col gap-1">
          <button @click="() => updateRow(g)" class="px-2 py-1 text-sm rounded bg-blue-600 text-white">更新</button>
          <button @click="() => toggle(g)" class="px-2 py-1 text-sm rounded bg-amber-600 text-white">
            {{ g.is_published ? '非公開' : '公開' }}
          </button>
          <button @click="() => remove(g)" class="px-2 py-1 text-sm rounded bg-red-600 text-white">削除</button>
        </div>
      </div>
    </div>
  </div>
  </AdminLayout>  
</template>
