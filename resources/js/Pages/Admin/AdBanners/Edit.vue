<script setup>
import { Link, useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  // コントローラ側で item:null or {…} を渡す
  item: { type: Object, default: null },
})

const isCreate = !props.item

const form = useForm({
  // 画像は新規作成時 required／編集時は任意
  image: null,
  url: props.item?.url ?? '',
  height: props.item?.height ?? 120,
  is_active: props.item?.is_active ?? true,
  starts_at: props.item?.starts_at ?? '',
  ends_at: props.item?.ends_at ?? '',
  priority: props.item?.priority ?? 100,
})

const submit = () => {
  if (isCreate) {
    form.post(route('admin.ad-banners.store'), { forceFormData: true })
  } else {
    form.post(route('admin.ad-banners.update', props.item.id), {
      forceFormData: true,
      _method: 'put',
    })
  }
}
</script>

<template>
  <AdminLayout active-key="Adbanners">
  <div class="p-6 text-black">
    <div class="flex items-center justify-between mb-4">
      <h1 class="text-xl font-bold">
        広告バナー {{ isCreate ? '新規作成' : `編集 #${props.item.id}` }}
      </h1>
      <Link :href="route('admin.ad-banners.index')" class="text-sky-400">一覧へ</Link>
    </div>

    <form @submit.prevent="submit" class="space-y-4">
      <!-- 画像 -->
      <div class="space-y-2">
        <label class="block text-sm">画像（高さおよそ120px推奨）</label>
        <div v-if="!isCreate && props.item?.image_url" class="mb-2">
          <img :src="props.item.image_url" alt="current" class="max-h-32 object-contain bg-black/30 rounded" />
        </div>
        <input type="file" accept="image/*" @change="e => form.image = e.target.files[0]" />
        <div class="text-xs text-red-300" v-if="form.errors.image">{{ form.errors.image }}</div>
      </div>

      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm">リンクURL</label>
          <input v-model="form.url" type="url" class="w-full px-3 py-2 rounded bg-white/10" placeholder="https://..." />
          <div class="text-xs text-red-300" v-if="form.errors.url">{{ form.errors.url }}</div>
        </div>
        <div>
          <label class="block text-sm">表示高さ(px)</label>
          <input v-model.number="form.height" type="number" min="60" max="600" class="w-full px-3 py-2 rounded bg-white/10" />
          <div class="text-xs text-red-300" v-if="form.errors.height">{{ form.errors.height }}</div>
        </div>
      </div>

      <div class="grid md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm">開始日時</label>
          <input v-model="form.starts_at" type="datetime-local" class="w-full px-3 py-2 rounded bg-white/10" />
          <div class="text-xs text-red-300" v-if="form.errors.starts_at">{{ form.errors.starts_at }}</div>
        </div>
        <div>
          <label class="block text-sm">終了日時</label>
          <input v-model="form.ends_at" type="datetime-local" class="w-full px-3 py-2 rounded bg-white/10" />
          <div class="text-xs text-red-300" v-if="form.errors.ends_at">{{ form.errors.ends_at }}</div>
        </div>
        <div>
          <label class="block text-sm">優先度（小さいほど先）</label>
          <input v-model.number="form.priority" type="number" min="0" max="65535" class="w-full px-3 py-2 rounded bg-white/10" />
          <div class="text-xs text-red-300" v-if="form.errors.priority">{{ form.errors.priority }}</div>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <input id="is_active" type="checkbox" v-model="form.is_active" />
        <label for="is_active">公開</label>
      </div>
      <div class="text-xs text-red-300" v-if="form.errors.is_active">{{ form.errors.is_active }}</div>

      <div class="pt-2">
        <button :disabled="form.processing"
                class="px-4 py-2 rounded bg-emerald-500 hover:brightness-110 disabled:opacity-60">
          {{ isCreate ? '作成' : '更新' }}
        </button>
      </div>
    </form>
  </div>
  </AdminLayout>
</template>
