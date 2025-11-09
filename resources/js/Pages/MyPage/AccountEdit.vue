<script setup>
import { useForm, Head } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({ user: Object })

const success = ref(null)

const form = useForm({
  email: props.user.email || '',
  phone: props.user.phone || '',
  password: '',
  password_confirmation: '',
})

function submit() {
  form.post(route('account.update'), {
    onSuccess: () => {
      success.value = '更新が完了しました。'
      form.reset('password', 'password_confirmation')
    },
  })
}
</script>

<template>
  <AppLayout title="アカウント情報">
    <Head title="アカウント情報" />

    <div class="max-w-lg mx-auto p-6 bg-white text-black rounded-2xl shadow mt-10">
      <h1 class="text-2xl font-semibold mb-6">アカウント情報の変更</h1>

      <div v-if="success" class="mb-4 text-green-600 text-sm">{{ success }}</div>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- メール -->
        <div>
          <label class="block text-sm font-medium mb-1">メールアドレス</label>
          <input
            v-model="form.email"
            type="email"
            placeholder="test@example.com"
            class="w-full rounded-md border-gray-300 px-3 py-2"
          />
          <div v-if="form.errors.email" class="text-sm text-red-600 mt-1">{{ form.errors.email }}</div>
        </div>

        <!-- 電話番号 -->
        <div>
          <label class="block text-sm font-medium mb-1">電話番号</label>
          <input
            v-model="form.phone"
            type="tel"
            placeholder="09012345678"
            class="w-full rounded-md border-gray-300 px-3 py-2"
          />
          <div v-if="form.errors.phone" class="text-sm text-red-600 mt-1">{{ form.errors.phone }}</div>
        </div>

        <!-- パスワード -->
        <div>
          <label class="block text-sm font-medium mb-1">新しいパスワード</label>
          <input
            v-model="form.password"
            type="password"
            placeholder="********"
            class="w-full rounded-md border-gray-300 px-3 py-2"
          />
        </div>

        <!-- パスワード確認 -->
        <div>
          <label class="block text-sm font-medium mb-1">新しいパスワード（確認）</label>
          <input
            v-model="form.password_confirmation"
            type="password"
            placeholder="********"
            class="w-full rounded-md border-gray-300 px-3 py-2"
          />
          <div v-if="form.errors.password" class="text-sm text-red-600 mt-1">{{ form.errors.password }}</div>
        </div>

        <!-- ボタン -->
        <div class="pt-4">
          <button
            type="submit"
            :disabled="form.processing"
            class="w-full py-3 rounded-md font-semibold text-white bg-yellow-500 hover:bg-yellow-400 disabled:opacity-50"
          >
            更新する
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
