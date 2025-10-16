<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
const props = defineProps({ item: Object })
const isCreate = !props.item

const form = useForm({
  name:        props.item?.name ?? '',
  area:        props.item?.area ?? '',
  address:     props.item?.address ?? '',
  phone:       props.item?.phone ?? '',
  website_url: props.item?.website_url ?? '',
  map_url:     props.item?.map_url ?? '',
  tags:        props.item?.tags ?? [],
  is_active:   props.item?.is_active ?? true,
  priority:    props.item?.priority ?? 100,
  cover_image: null,
})
const addTag = (e) => { const v = e.target.value.trim(); if (v) { form.tags.push(v); e.target.value=''; } }
const removeTag = (i) => form.tags.splice(i,1)

const submit = () => {
  if (isCreate) form.post('/admin/hotels', { forceFormData: true })
  else form.post(`/admin/hotels/${props.item.id}`, { _method:'put', forceFormData: true })
}
</script>

<template>
  <AdminLayout active-key="Hotels">
  <div class="p-6 text-black space-y-4">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-bold">ホテル {{ isCreate ? '新規' : `編集 #${props.item.id}` }}</h1>
      <Link href="/admin/hotels" class="text-sky-400">一覧へ</Link>
    </div>

    <form @submit.prevent="submit" class="space-y-4">
      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm">名称</label>
          <input v-model="form.name" class="w-full px-3 py-2 rounded bg-white/10"/>
        </div>
        <div>
          <label class="block text-sm">エリア</label>
          <input v-model="form.area" class="w-full px-3 py-2 rounded bg-white/10"/>
        </div>
      </div>

      <div>
        <label class="block text-sm">住所</label>
        <input v-model="form.address" class="w-full px-3 py-2 rounded bg-white/10"/>
      </div>

      <div class="grid md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm">電話</label>
          <input v-model="form.phone" class="w-full px-3 py-2 rounded bg-white/10"/>
        </div>
        <div>
          <label class="block text-sm">WebサイトURL</label>
          <input v-model="form.website_url" type="url" class="w-full px-3 py-2 rounded bg-white/10"/>
        </div>
        <div>
          <label class="block text-sm">地図URL</label>
          <input v-model="form.map_url" type="url" class="w-full px-3 py-2 rounded bg-white/10"/>
        </div>
      </div>

      <div>
        <label class="block text-sm">タグ</label>
        <div class="flex gap-2 flex-wrap mb-2">
          <span v-for="(t,i) in form.tags" :key="i" class="px-2 py-1 text-xs rounded bg-white/20">
            {{ t }} <button type="button" @click="removeTag(i)" class="ml-1 opacity-70">×</button>
          </span>
        </div>
        <input @keyup.enter.prevent="addTag" placeholder="タグを入力してEnter"
               class="w-full px-3 py-2 rounded bg-white/10"/>
      </div>

      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm">優先度（小さいほど先）</label>
          <input v-model.number="form.priority" type="number" min="0" max="65535" class="w-full px-3 py-2 rounded bg-white/10"/>
        </div>
        <div>
          <label class="block text-sm">代表画像</label>
          <input type="file" accept="image/*" @change="e => form.cover_image = e.target.files[0]" />
          <div v-if="!isCreate && props.item?.image_url" class="mt-2">
            <img :src="props.item.image_url" class="max-h-32 rounded object-contain"/>
          </div>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <input id="is_active" type="checkbox" v-model="form.is_active">
        <label for="is_active">公開</label>
      </div>

      <button :disabled="form.processing" class="px-4 py-2 rounded bg-emerald-500 hover:brightness-110 disabled:opacity-60">
        {{ isCreate ? '作成' : '更新' }}
      </button>
    </form>
  </div>
  </AdminLayout>
</template>
