<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const warmed = ref(false);
onMounted(async () => {
  warmed.value = false;
  try {
    await fetch('/session-probe', { credentials: 'include' });
    warmed.value = true;
  } catch (_) {
    console.warn('session-probe failed');
  }
});


defineProps({
  canResetPassword: {
    type: Boolean,
  },
  status: {
    type: String,
  },
});

const form = useForm({
  login: '',      // ← 統一フィールド（メール or 電話）
  password: '',
  remember: false,
});

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  });
};
</script>

<template>
  <GuestLayout>
    <Head title="ログイン" />

    <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
      {{ status }}
    </div>

    <form @submit.prevent="submit">
      <!-- ✅ メールまたは電話番号 -->
      <div>
        <InputLabel for="login" value="メールアドレス または 電話番号" />

        <TextInput
          id="login"
          type="text"
          class="mt-1 block w-full"
          v-model="form.login"
          required
          autofocus
          placeholder="例）test@example.com または 09012345678"
        />

        <InputError class="mt-2" :message="form.errors.login" />
      </div>

      <!-- ✅ パスワード -->
      <div class="mt-4">
        <InputLabel for="password" value="パスワード" />

        <TextInput
          id="password"
          type="password"
          class="mt-1 block w-full"
          v-model="form.password"
          required
          autocomplete="current-password"
        />

        <InputError class="mt-2" :message="form.errors.password" />
      </div>

      <!-- ✅ Remember me -->
      <div class="mt-4 block">
        <label class="flex items-center">
          <Checkbox name="remember" v-model:checked="form.remember" />
          <span class="ms-2 text-sm text-gray-600">ログイン状態を保持</span>
        </label>
      </div>

      <!-- ✅ パスワードリセット + ログインボタン -->
      <div class="mt-4 flex items-center justify-end">
        <Link
          v-if="canResetPassword"
          :href="route('password.request')"
          class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
        >
          パスワードをお忘れですか？
        </Link>

        <PrimaryButton
          :disabled="!warmed || form.processing"
          class="ml-4 inline-flex items-center justify-center
                 px-6 py-3 rounded-xl text-base font-bold tracking-wide
                 bg-gradient-to-r from-yellow-400 via-yellow-300 to-amber-400
                 text-black shadow-[0_6px_16px_rgba(255,200,0,0.35)]
                 hover:from-yellow-300 hover:to-yellow-400
                 focus:outline-none focus:ring-2 focus:ring-yellow-300 focus:ring-offset-2
                 disabled:opacity-50 disabled:cursor-not-allowed
                 transition-transform duration-150 active:scale-[0.98]"
        >
          <svg
            v-if="form.processing"
            class="mr-2 h-4 w-4 animate-spin"
            viewBox="0 0 24 24"
            fill="none"
          >
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            />
            <path
              class="opacity-75"
              d="M4 12a8 8 0 018-8v4"
              stroke="currentColor"
              stroke-width="4"
            />
          </svg>
          ログイン
        </PrimaryButton>
      </div>
    </form>
  </GuestLayout>
</template>
