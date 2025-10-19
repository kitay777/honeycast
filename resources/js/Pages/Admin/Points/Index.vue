<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
const props = defineProps({ users: Object, filters: Object })
const q = ref(props.filters?.q ?? '')
const search = () => router.get('/admin/points', { q: q.value }, { preserveState: true, replace: true })
const form = useForm({ user_id: '', delta: 0, reason: '' })
const submit = () => form.post('/admin/points/adjust', { onSuccess: () => form.reset('delta','reason') })
</script>

<template>
  <AdminLayout active-key="Points">
    <div class="p-6 space-y-4">
      <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">ポイント管理</h1>
        <div class="flex gap-2">
          <input v-model="q" @keyup.enter="search" class="px-3 py-2 rounded border" placeholder="名前/メール"/>
          <button @click="search" class="px-3 py-2 rounded bg-emerald-600 text-white">検索</button>
        </div>
      </div>

      <div class="grid md:grid-cols-2 gap-6">
        <div class="rounded border p-3 overflow-x-auto">
          <table class="w-full text-sm">
            <thead><tr><th>ID</th><th>ユーザー</th><th>メール</th><th class="text-right">残高</th></tr></thead>
            <tbody>
              <tr v-for="u in props.users.data" :key="u.id" class="border-t">
                <td>{{ u.id }}</td><td>{{ u.name }}</td><td class="truncate">{{ u.email }}</td>
                <td class="text-right font-semibold">{{ u.points.toLocaleString() }}</td>
              </tr>
            </tbody>
          </table>
          <div class="mt-3 flex gap-2">
            <a v-for="(l,i) in props.users.links" :key="i" :href="l.url || '#'"
               class="px-2 py-1 rounded border"
               :class="{ 'bg-emerald-600 text-white': l.active, 'opacity-50 pointer-events-none': !l.url }"
               v-html="l.label"/>
          </div>
        </div>

        <div class="rounded border p-3">
          <h2 class="font-semibold mb-2">ポイント調整</h2>
          <form @submit.prevent="submit" class="space-y-3">
            <div>
              <label class="block text-sm">ユーザーID</label>
              <input v-model="form.user_id" type="number" class="w-full px-3 py-2 rounded border" required/>
              <div class="text-xs text-red-600" v-if="form.errors.user_id">{{ form.errors.user_id }}</div>
            </div>
            <div>
              <label class="block text-sm">増減（例 +100 / -50）</label>
              <input v-model.number="form.delta" type="number" class="w-full px-3 py-2 rounded border" required/>
              <div class="text-xs text-red-600" v-if="form.errors.delta">{{ form.errors.delta }}</div>
            </div>
            <div>
              <label class="block text-sm">理由（任意）</label>
              <input v-model="form.reason" class="w-full px-3 py-2 rounded border"/>
            </div>
            <button :disabled="form.processing" class="px-4 py-2 rounded bg-sky-600 text-white disabled:opacity-60">
              実行
            </button>
          </form>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
