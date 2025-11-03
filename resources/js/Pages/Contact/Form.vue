<!-- resources/js/Pages/Contact/Form.vue -->
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, Link } from '@inertiajs/vue3'

const props = defineProps({
  supportEmail: { type: String, default: 'support@example.com' },
  message: { type: String, default: 'お問い合わせフォーム' },
})

// Inertia form
const form = useForm({
  name: '',
  email: '',
  message: '',
})

const submit = () => {
  form.post('/contact', {
    onSuccess: () => {
      alert('お問い合わせを送信しました。ありがとうございます。')
      form.reset()
    },
    onError: () => {
      alert('送信に失敗しました。入力内容を確認してください。')
    },
  })
}
</script>

<template>
  <AppLayout>
    <div
      class="min-h-dvh px-4 py-10 text-white/90
             bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%]"
    >
      <div class="max-w-xl mx-auto text-center">
        <h1 class="text-2xl font-bold tracking-wide mb-6">{{ message }}</h1>

        <form
          @submit.prevent="submit"
          class="space-y-4 text-left bg-black/30 p-6 rounded-md border border-white/10 shadow-lg"
        >
          <div>
            <label class="block text-sm font-semibold mb-1">お名前</label>
            <input v-model="form.name" type="text" class="w-full rounded-md text-black p-2" required />
            <div v-if="form.errors.name" class="text-red-400 text-xs mt-1">{{ form.errors.name }}</div>
          </div>

          <div>
            <label class="block text-sm font-semibold mb-1">メールアドレス</label>
            <input v-model="form.email" type="email" class="w-full rounded-md text-black p-2" required />
            <div v-if="form.errors.email" class="text-red-400 text-xs mt-1">{{ form.errors.email }}</div>
          </div>

          <div>
            <label class="block text-sm font-semibold mb-1">お問い合わせ内容</label>
            <textarea
              v-model="form.message"
              rows="5"
              class="w-full rounded-md text-black p-2"
              placeholder="お問い合わせ内容をご記入ください。"
              required
            ></textarea>
            <div v-if="form.errors.message" class="text-red-400 text-xs mt-1">{{ form.errors.message }}</div>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full bg-yellow-500 hover:bg-yellow-600 text-black font-bold px-4 py-2 rounded transition"
          >
            <span v-if="!form.processing">送信する</span>
            <span v-else>送信中...</span>
          </button>
        </form>

        <div class="mt-6 text-sm text-white/70">
          <p>
            お急ぎの方は
            <a
              :href="`mailto:${props.supportEmail}`"
              class="underline underline-offset-2 hover:opacity-80"
            >
              {{ props.supportEmail }}
            </a>
            までご連絡ください。
          </p>
          <p class="mt-2">
            <Link href="/dashboard" class="underline underline-offset-2 hover:opacity-80">
              ← ダッシュボードへ戻る
            </Link>
          </p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
