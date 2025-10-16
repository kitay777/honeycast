<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  item: { type: Object, default: null },
})
const isCreate = !props.item

const form = useForm({
  message: props.item?.message ?? '',
  url: props.item?.url ?? '',
  speed: props.item?.speed ?? 60,
  is_active: props.item?.is_active ?? true,
  starts_at: props.item?.starts_at ?? '',
  ends_at: props.item?.ends_at ?? '',
  priority: props.item?.priority ?? 100,
  bg_color: props.item?.bg_color ?? '#111111',
  text_color: props.item?.text_color ?? '#FFE08A',
})

const submit = () => {
  if (isCreate) {
    form.post(route('admin.text-banners.store'))
  } else {
    form.put(route('admin.text-banners.update', props.item.id))
  }
}
</script>

<template>
    <AdminLayout active-key="text-banners">
  <div class="p-6 text-black">
    <div class="flex items-center justify-between mb-4">
      <h1 class="text-xl font-bold">
        テキストバナー {{ isCreate ? '新規作成' : `編集 #${props.item.id}` }}
      </h1>
      <Link :href="route('admin.text-banners.index')" class="text-sky-400">一覧へ</Link>
    </div>

    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label class="block text-sm">テキスト</label>
        <textarea v-model="form.message" rows="2" class="w-full px-3 py-2 rounded bg-white/10"></textarea>
        <div class="text-xs text-red-300" v-if="form.errors.message">{{ form.errors.message }}</div>
      </div>

      <div class="grid md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm">リンクURL</label>
          <input v-model="form.url" type="url" class="w-full px-3 py-2 rounded bg-white/10" placeholder="https://..." />
          <div class="text-xs text-red-300" v-if="form.errors.url">{{ form.errors.url }}</div>
        </div>
        <div>
          <label class="block text-sm">速度（px/s 目安）</label>
          <input v-model.number="form.speed" type="number" min="10" max="500" class="w-full px-3 py-2 rounded bg-white/10" />
          <div class="text-xs text-red-300" v-if="form.errors.speed">{{ form.errors.speed }}</div>
        </div>
        <div>
          <label class="block text-sm">優先度</label>
          <input v-model.number="form.priority" type="number" min="0" max="65535" class="w-full px-3 py-2 rounded bg-white/10" />
          <div class="text-xs text-red-300" v-if="form.errors.priority">{{ form.errors.priority }}</div>
        </div>
      </div>

      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm">背景色</label>
          <input v-model="form.bg_color" type="text" class="w-full px-3 py-2 rounded bg-white/10" placeholder="#111111" />
        </div>
        <div>
          <label class="block text-sm">文字色</label>
          <input v-model="form.text_color" type="text" class="w-full px-3 py-2 rounded bg-white/10" placeholder="#FFE08A" />
        </div>
      </div>

      <div class="grid md:grid-cols-2 gap-4">
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
