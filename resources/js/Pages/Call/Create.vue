<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  defaults: { type: Object, default: () => ({}) }
})

const form = useForm({
  place:       props.defaults.place ?? '',
  cast_count:  1,
  guest_count: 1,
  nomination:  props.defaults.nomination ?? '',
  date:        '',
  start_time:  '20:00',
  end_time:    '22:00',
  set_plan:    '',
  game_option: '',
  note:        '',
})

const submit = () => form.post(route('call.store'))
</script>

<template>
  <AppLayout>
    <div class="pt-6 pb-28 px-4 text-white/90
                bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%]">

      <h1 class="text-center text-2xl tracking-[0.6em] mb-4">呼　ぶ</h1>
      <div class="h-[2px] w-full bg-gradient-to-r from-[#c8a64a] via-[#e6d08a] to-[#c8a64a] mb-6"></div>

      <form @submit.prevent="submit" class="max-w-2xl mx-auto bg-[#2b241b]/60 rounded-lg border border-[#d1b05a]/30 p-5 space-y-5">

        <!-- 共通スタイル：左ラベル / 右フィールド -->
        <div class="grid grid-cols-5 items-center gap-3">
          <label class="col-span-2 text-lg">場所</label>
          <input v-model="form.place" type="text" class="col-span-3 h-11 rounded px-3 text-black" />
        </div>
        <p v-if="form.errors.place" class="text-red-300 text-sm -mt-3">{{ form.errors.place }}</p>

        <div class="grid grid-cols-5 items-center gap-3">
          <label class="col-span-2 text-lg">cast人数</label>
          <div class="col-span-3 flex items-center gap-2">
            <input v-model.number="form.cast_count" type="number" min="1" class="h-11 w-28 rounded px-3 text-black" />
            <span>名</span>
          </div>
        </div>

        <div class="grid grid-cols-5 items-center gap-3">
          <label class="col-span-2 text-lg">お客様の人数</label>
          <div class="col-span-3 flex items-center gap-2">
            <input v-model.number="form.guest_count" type="number" min="1" class="h-11 w-28 rounded px-3 text-black" />
            <span>名</span>
          </div>
        </div>

        <div class="grid grid-cols-5 items-center gap-3">
          <label class="col-span-2 text-lg">指名</label>
          <input v-model="form.nomination" type="text" class="col-span-3 h-11 rounded px-3 text-black" placeholder="任意（キャスト名など）" />
        </div>

        <div class="grid grid-cols-5 items-center gap-3">
          <label class="col-span-2 text-lg">日</label>
          <input v-model="form.date" type="date" class="col-span-1 h-11 rounded px-3 text-black" />
          <div class="col-span-2"></div>
        </div>

        <div class="grid grid-cols-5 items-center gap-3">
          <label class="col-span-2 text-lg">時間</label>
          <div class="col-span-3 flex items-center gap-2">
            <input v-model="form.start_time" type="time" class="h-11 rounded px-3 text-black" />
            <span>〜</span>
            <input v-model="form.end_time" type="time" class="h-11 rounded px-3 text-black" />
          </div>
        </div>
        <p v-if="form.errors.start_time || form.errors.end_time" class="text-red-300 text-sm -mt-3">
          {{ form.errors.start_time || form.errors.end_time }}
        </p>

        <div class="grid grid-cols-5 items-center gap-3">
          <label class="col-span-2 text-lg">set</label>
          <select v-model="form.set_plan" class="col-span-3 h-11 rounded px-3 text-black">
            <option value="">選択してください</option>
            <option value="1set">1 set</option>
            <option value="2set">2 set</option>
            <option value="3set">3 set</option>
          </select>
        </div>

        <div class="grid grid-cols-5 items-center gap-3">
          <label class="col-span-2 text-lg">ゲームオプション</label>
          <input v-model="form.game_option" type="text" class="col-span-3 h-11 rounded px-3 text-black" placeholder="例：UNO / 人狼 など" />
        </div>

        <div class="grid grid-cols-5 items-center gap-3">
          <label class="col-span-2 text-lg">備考</label>
          <textarea v-model="form.note" rows="3" class="col-span-3 rounded px-3 py-2 text-black" placeholder="要望や集合場所の詳細など"></textarea>
        </div>

        <div class="pt-2 flex justify-center">
          <button :disabled="form.processing"
                  class="w-72 h-12 rounded-full text-xl bg-gradient-to-r from-[#caa14b] to-[#f0e1b1]
                         text-black font-bold tracking-[0.5em] shadow">
            予 約 す る
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
