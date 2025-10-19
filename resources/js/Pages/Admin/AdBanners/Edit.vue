<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({ item: Object })
const isCreate = !props.item

const form = useForm({
  url:        props.item?.url ?? '',
  height:     props.item?.height ?? 120,
  is_active:  props.item?.is_active ?? true,
  starts_at:  props.item?.starts_at ?? '',
  ends_at:    props.item?.ends_at ?? '',
  priority:   props.item?.priority ?? 100,
  image:      null, // 画像はここに File を入れる
})

const submit = () => {
  if (isCreate) {
    // 新規: POST /admin/ad-banners
    form.post(route('admin.ad-banners.store'), { forceFormData: true })
  } else {
    // id を数値に正規化（念のため）
    const id = parseInt(String(props.item?.id ?? '').replace(/[^\d]/g, ''), 10)
    if (!id) { console.error('Invalid ad_banner id:', props.item?.id); return }

    // 更新: POST + _method=PUT（multipart 安定）
    form
      .transform(d => ({ ...d, _method: 'PUT' }))
      .post(route('admin.ad-banners.update', { ad_banner: id }), {
        forceFormData: true,
        headers: { 'X-HTTP-Method-Override': 'PUT' }, // プロキシ対策（保険）
        onFinish: () => form.reset('image'),
      })
  }
}
</script>






<template>
    <AdminLayout active-key="AdBanners">
  <div class="p-6 text-black space-y-4">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-bold">広告 {{ isCreate ? '新規' : `編集 #${props.item.id}` }}</h1>
      <Link href="/admin/ad_banners" class="text-sky-400">一覧へ</Link>
    </div>

    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label class="block text-sm">タイトル</label>
        <input v-model="form.title" class="w-full px-3 py-2 rounded bg-white/10" />
      </div>

      <div>
        <label class="block text-sm">本文</label>
        <textarea v-model="form.body" rows="5" class="w-full px-3 py-2 rounded bg-white/10"></textarea>
      </div>

      <div class="grid md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm">場所</label>
          <input v-model="form.place" class="w-full px-3 py-2 rounded bg-white/10" />
        </div>
        <div>
          <label class="block text-sm">開始</label>
          <input v-model="form.starts_at" type="datetime-local" class="w-full px-3 py-2 rounded bg-white/10" />
        </div>
        <div>
          <label class="block text-sm">終了</label>
          <input v-model="form.ends_at" type="datetime-local" class="w-full px-3 py-2 rounded bg-white/10" />
        </div>
      </div>

      <div class="flex items-center gap-4">
        <label class="flex items-center gap-2">
          <input type="checkbox" v-model="form.is_all_day" /> 終日
        </label>
        <label class="flex items-center gap-2">
          <input type="checkbox" v-model="form.is_active" /> 公開
        </label>
      </div>

      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm">優先度（小さいほど先）</label>
          <input v-model.number="form.priority" type="number" min="0" max="65535" class="w-full px-3 py-2 rounded bg-white/10" />
        </div>
        <div>
          <label class="block text-sm">画像（任意）</label>
          <input type="file" accept="image/*" @change="e => form.image = e.target.files[0]" />
          <div v-if="!isCreate && props.item?.image_url" class="mt-2">
            <img :src="props.item.image_url" class="max-h-32 rounded object-contain"/>
          </div>
        </div>
      </div>

      <button :disabled="form.processing" class="px-4 py-2 rounded bg-emerald-500 hover:brightness-110 disabled:opacity-60">
        {{ isCreate ? '作成' : '更新' }}
      </button>
    </form>
  </div>
  </AdminLayout>  
</template>
