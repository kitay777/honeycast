<script setup>
import { useForm, Link } from '@inertiajs/vue3'

const form = useForm({
  // User
  name: '', email: '', area: '',
  password: '', password_confirmation: '',

  // CastProfile（最低限）
  nickname: '',
  age: '', 
  height_cm: '',
  style: '', 
  alcohol: '', 
  mbti: '',
  cast_area: '',
  tags: [],
  freeword: '',
  photo: null,
})

const onPhotoChange = (e) => {
  const f = e.target.files?.[0] ?? null
  form.photo = f
}

const submit = () => {
  if (form.age === null) form.age = ''
  if (form.height_cm === null) form.height_cm = ''
  form.post(route('cast.register.store'), { forceFormData: true })
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-black text-white/90">
    <div class="w-full max-w-md p-6 bg-white/10 rounded-xl border border-white/20">
      <h1 class="text-2xl font-semibold mb-4">キャスト登録</h1>

      <!-- User -->
      <div class="space-y-3">
        <div>
          <label class="text-sm">お名前(必須)</label>
          <input v-model="form.name" class="w-full h-11 rounded px-3 text-black" />
          <p v-if="form.errors.name" class="text-red-300 text-xs mt-1">{{ form.errors.name }}</p>
        </div>
        <div>
          <label class="text-sm">メール(必須)</label>
          <input v-model="form.email" type="email" class="w-full h-11 rounded px-3 text-black" />
          <p v-if="form.errors.email" class="text-red-300 text-xs mt-1">{{ form.errors.email }}</p>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-sm">パスワード(必須)</label>
            <input v-model="form.password" type="password" class="w-full h-11 rounded px-3 text-black" />
            <p v-if="form.errors.password" class="text-red-300 text-xs mt-1">{{ form.errors.password }}</p>
          </div>
          <div>
            <label class="text-sm">確認(必須)</label>
            <input v-model="form.password_confirmation" type="password" class="w-full h-11 rounded px-3 text-black" />
          </div>
        </div>
        <div>
          <label class="text-sm">お住まいのエリア（任意）</label>
          <input v-model="form.area" class="w-full h-11 rounded px-3 text-black" />
        </div>
      </div>

      <!-- CastProfile（必要なら最小限だけ） -->
      <div class="mt-6 space-y-3">
        <div>
          <label class="text-sm">ニックネーム(必須)</label>
          <input v-model="form.nickname" class="w-full h-11 rounded px-3 text-black" />
        <p v-if="form.errors.nickname" class="text-red-300 text-xs mt-1">{{ form.errors.nickname }}</p>
      </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-sm">年齢(必須)</label>
            <input v-model.number="form.age" type="number" min="18" max="99" class="w-full h-11 rounded px-3 text-black" />
          <p v-if="form.errors.age" class="text-red-300 text-xs mt-1">{{ form.errors.age }}</p>
          </div>
          <div>
            <label class="text-sm">身長(cm)(必須)</label>
            <input v-model.number="form.height_cm" type="number" min="120" max="220" class="w-full h-11 rounded px-3 text-black" />
          <p v-if="form.errors.height_cm" class="text-red-300 text-xs mt-1">{{ form.errors.height_cm }}</p>
          </div>
        </div>

        <div>
          <label class="text-sm">自己紹介（任意）</label>
          <textarea v-model="form.freeword" rows="3" class="w-full rounded px-3 py-2 text-black"></textarea>
        </div>

        <div>
          <label class="text-sm">顔写真（任意）</label>
          <input type="file" accept="image/*" @change="onPhotoChange" />
          <p v-if="form.errors.photo" class="text-red-300 text-xs mt-1">{{ form.errors.photo }}</p>
        </div>
      </div>

      <button
        :disabled="form.processing"
        @click="submit"
        class="mt-6 w-full h-12 rounded-md bg-yellow-200 text-black font-semibold"
      >
        登録する
      </button>

      <div class="mt-4 text-sm text-white/70">
        すでにアカウントをお持ちの方は
        <Link href="/login" class="underline text-yellow-200">ログイン</Link>
      </div>
    </div>
  </div>
</template>
