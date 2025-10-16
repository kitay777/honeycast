<script setup>
import { useForm, Link } from '@inertiajs/vue3'
const props = defineProps({ item: Object })
const isCreate = !props.item

const form = useForm({
  title:        props.item?.title ?? '',
  body:         props.item?.body ?? '',
  url:          props.item?.url ?? '',
  published_at: props.item?.published_at ?? '',
  is_active:    props.item?.is_active ?? true,
  priority:     props.item?.priority ?? 100,
})

const submit = () => {
  if (isCreate) form.post('/admin/news')
  else form.put(`/admin/news/${props.item.id}`)
}
</script>

<template>
  <div class="p-6 text-black space-y-4">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-bold">新着情報 {{ isCreate ? '新規' : `編集 #${props.item.id}` }}</h1>
      <Link href="/admin/news" class="text-sky-400">一覧へ</Link>
    </div>

    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label class="block text-sm">タイトル</label>
        <input v-model="form.title" class="w-full px-3 py-2 rounded bg-white/10" />
        <div class="text-xs text-red-300" v-if="form.errors.title">{{ form.errors.title }}</div>
      </div>

      <div>
        <label class="block text-sm">本文</label>
        <textarea v-model="form.body" rows="5" class="w-full px-3 py-2 rounded bg-white/10"></textarea>
        <div class="text-xs text-red-300" v-if="form.errors.body">{{ form.errors.body }}</div>
      </div>

      <div class="grid md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm">リンクURL</label>
          <input v-model="form.url" type="url" class="w-full px-3 py-2 rounded bg-white/10" placeholder="https://..." />
          <div class="text-xs text-red-300" v-if="form.errors.url">{{ form.errors.url }}</div>
        </div>
        <div>
          <label class="block text-sm">公開日時</label>
          <input v-model="form.published_at" type="datetime-local" class="w-full px-3 py-2 rounded bg-white/10" />
          <div class="text-xs text-red-300" v-if="form.errors.published_at">{{ form.errors.published_at }}</div>
        </div>
        <div>
          <label class="block text-sm">優先度（小さいほど先）</label>
          <input v-model.number="form.priority" type="number" min="0" max="65535" class="w-full px-3 py-2 rounded bg-white/10" />
          <div class="text-xs text-red-300" v-if="form.errors.priority">{{ form.errors.priority }}</div>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <input id="is_active" type="checkbox" v-model="form.is_active">
        <label for="is_active">公開</label>
      </div>

      <button :disabled="form.processing" class="px-4 py-2 rounded bg-emerald-500 hover:brightness-110 disabled:opacity-60">
        {{ isCreate ? '作成' : '更新' }}
      </button>
    </form>
  </div>
</template>
