<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'

const form = useForm({
  title: '',
  body: '',
  image: null,
})

const submit = () => form.post(route('tweets.store'), { forceFormData: true })
</script>

<template>
  <AppLayout>
    <div class="pt-6 pb-28 px-6 text-white/90
                bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%]">

      <h1 class="text-center text-2xl mb-6">ツイート投稿</h1>

      <form @submit.prevent="submit" class="max-w-xl mx-auto space-y-4">
        <div>
          <label class="block mb-1">題名</label>
          <input v-model="form.title" type="text" class="w-full h-10 rounded px-3 text-black"/>
        </div>
        <div>
          <label class="block mb-1">本文</label>
          <textarea v-model="form.body" rows="5" class="w-full rounded px-3 py-2 text-black"></textarea>
        </div>
        <div>
          <label class="block mb-1">画像</label>
          <input type="file" @change="e=>form.image=e.target.files[0]" class="w-full text-sm"/>
        </div>

        <button type="submit" class="w-full h-12 rounded bg-yellow-500 text-black font-bold">投稿</button>
      </form>
    </div>
  </AppLayout>
</template>
