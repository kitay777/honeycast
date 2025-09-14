<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'

const form = useForm({
  name: '',
  area: '',
  phone: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const submit = () => {
  form.post(route('register'))
}

const goBack = () => {
  router.visit('/') // もしくは router.back()
}
</script>

<template>
  <Head title="新規登録" />

  <!-- 画面全体 -->
  <div class="min-h-dvh w-screen flex items-center justify-center bg-black">
    <!-- iPhone縦長っぽいキャンバス（PCでも見やすく） -->
    <div
      class="relative w-full h-dvh md:w-[390px] md:h-[844px] mx-auto
             bg-no-repeat bg-center bg-[length:100%_100%] overflow-y-auto"
      style="background-image: url('/assets/imgs/back.png');"
    >
      <!-- 内容 -->
      <div class="px-8 pt-20 pb-12 text-white/90">
        <h1 class="text-2xl font-semibold tracking-wider mb-10">
          ご登録情報をご入力下さい。
        </h1>

        <!-- ラベル＋入力 -->
        <form @submit.prevent="submit" class="space-y-7">
          <!-- 名前 -->
          <div>
            <label class="block mb-2 text-sm">名前（ニックネーム可）</label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full h-12 rounded-md bg-white text-black px-3
                     outline-none ring-2 ring-[#ff9e4a] ring-offset-2 ring-offset-transparent
                     focus:ring-[#ffba6a] shadow-[0_0_0_3px_rgba(0,0,0,0.1)]"
            />
            <div v-if="form.errors.name" class="mt-1 text-xs text-red-300">{{ form.errors.name }}</div>
          </div>

          <!-- エリア -->
          <div>
            <label class="block mb-2 text-sm">エリア</label>
            <select
              v-model="form.area"
              class="w-full h-12 rounded-md bg-white text-black px-3
                     outline-none ring-2 ring-[#ff9e4a] ring-offset-2 focus:ring-[#ffba6a]"
            >
              <option value="" disabled>選択してください</option>
              <option>北海道・東北</option>
              <option>関東</option>
              <option>中部</option>
              <option>近畿</option>
              <option>中国・四国</option>
              <option>九州・沖縄</option>
            </select>
            <div v-if="form.errors.area" class="mt-1 text-xs text-red-300">{{ form.errors.area }}</div>
          </div>

          <!-- 電話番号 -->
          <div>
            <label class="block mb-2 text-sm">電話番号</label>
            <input
              v-model="form.phone"
              type="tel"
              inputmode="tel"
              class="w-full h-12 rounded-md bg-white text-black px-3
                     outline-none ring-2 ring-[#ff9e4a] ring-offset-2 focus:ring-[#ffba6a]"
            />
            <div v-if="form.errors.phone" class="mt-1 text-xs text-red-300">{{ form.errors.phone }}</div>
          </div>

          <!-- メール -->
          <div>
            <label class="block mb-2 text-sm">メールアドレス</label>
            <input
              v-model="form.email"
              type="email"
              required
              class="w-full h-12 rounded-md bg-white text-black px-3
                     outline-none ring-2 ring-[#ff9e4a] ring-offset-2 focus:ring-[#ffba6a]"
            />
            <div v-if="form.errors.email" class="mt-1 text-xs text-red-300">{{ form.errors.email }}</div>
          </div>

          <!-- パスワード -->
          <div>
            <label class="block mb-2 text-sm">希望パスワード</label>
            <input
              v-model="form.password"
              type="password"
              required
              class="w-full h-12 rounded-md bg-white text-black px-3
                     outline-none ring-2 ring-[#ff9e4a] ring-offset-2 focus:ring-[#ffba6a]"
            />
            <div v-if="form.errors.password" class="mt-1 text-xs text-red-300">{{ form.errors.password }}</div>
          </div>

          <!-- 確認 -->
          <input v-model="form.password_confirmation" type="password" class="hidden" />

          <!-- ボタンたち -->
          <div class="mt-6 space-y-4">
            <!-- 登録 -->
            <button
              type="submit"
              :disabled="form.processing"
              class="w-full h-12 rounded-md font-bold tracking-[0.5em]
                     bg-[#7a560f] text-white shadow-md border border-[#c79a2b]
                     hover:brightness-110 active:scale-[0.99] transition"
            >
              登　録
            </button>

            <!-- 戻る -->
            <button
              type="button"
              @click="goBack"
              class="w-full h-12 rounded-md font-bold tracking-[0.5em]
                     bg-[#a49a88] text-black/90 shadow
                     hover:brightness-110 active:scale-[0.99] transition"
            >
              戻　る
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
