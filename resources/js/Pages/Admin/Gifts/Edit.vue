<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
const props = defineProps({ item: Object })
const isCreate = !props.item

const form = useForm({
  name: props.item?.name ?? '',
  description: props.item?.description ?? '',
  present_points: props.item?.present_points ?? 100,
  cast_points: props.item?.cast_points ?? 50,
  is_active: props.item?.is_active ?? true,
  priority: props.item?.priority ?? 100,
  image: null,
})
const submit = () => {
  if (isCreate) {
    form.post('/admin/gifts', { forceFormData: true })
  } else {
    form.transform(d => ({ ...d, _method: 'PUT' }))
        .post(`/admin/gifts/${props.item.id}`, { forceFormData: true })
  }
}
</script>

<template>
  <AdminLayout active-key="gifts">
    <div class="p-6 space-y-4">
      <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">プレゼント {{ isCreate ? '新規' : `編集 #${props.item.id}` }}</h1>
        <Link href="/admin/gifts" class="text-sky-600">一覧へ</Link>
      </div>

      <form @submit.prevent="submit" class="space-y-4 bg-white rounded-2xl shadow p-4">
        <div class="grid md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm">名称</label>
            <input v-model="form.name" class="w-full px-3 py-2 border rounded" />
            <div class="text-xs text-red-600" v-if="form.errors.name">{{ form.errors.name }}</div>
          </div>
          <div>
            <label class="block text-sm">優先度（小さいほど先）</label>
            <input v-model.number="form.priority" type="number" min="0" max="65535" class="w-full px-3 py-2 border rounded" />
          </div>
        </div>

        <div>
          <label class="block text-sm">説明（任意）</label>
          <textarea v-model="form.description" rows="3" class="w-full px-3 py-2 border rounded"></textarea>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm">プレゼントポイント（ユーザー消費）</label>
            <input v-model.number="form.present_points" type="number" min="1" class="w-full px-3 py-2 border rounded" />
            <div class="text-xs text-red-600" v-if="form.errors.present_points">{{ form.errors.present_points }}</div>
          </div>
          <div>
            <label class="block text-sm">キャスト受取ポイント</label>
            <input v-model.number="form.cast_points" type="number" min="0" class="w-full px-3 py-2 border rounded" />
            <div class="text-xs text-red-600" v-if="form.errors.cast_points">{{ form.errors.cast_points }}</div>
            <div class="text-xs text-gray-500 mt-1">※ プレゼントポイント以下にしてください</div>
          </div>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
          <div class="flex items-center gap-3">
            <input id="active" type="checkbox" v-model="form.is_active" />
            <label for="active" class="text-sm">公開</label>
          </div>
          <div>
            <label class="block text-sm">イメージ画像</label>
            <input type="file" accept="image/*" @change="e => form.image = e.target.files?.[0] ?? null" />
            <div class="text-xs text-red-600" v-if="form.errors.image">{{ form.errors.image }}</div>
            <div v-if="!isCreate && props.item?.image_url" class="mt-2">
              <img :src="props.item.image_url" class="h-24 object-contain rounded bg-gray-50 p-1" />
            </div>
          </div>
        </div>

        <div class="pt-2">
          <button :disabled="form.processing" class="px-4 py-2 rounded bg-emerald-600 text-white disabled:opacity-60">
            {{ isCreate ? '作成' : '更新' }}
          </button>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>
