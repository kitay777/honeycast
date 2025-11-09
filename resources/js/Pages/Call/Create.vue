<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  defaults: { type: Object, default: () => ({}) },
  coupons: { type: Array, default: () => [] },
})


// ✅ フォーム初期化
const form = useForm({
  place:       props.defaults.place ?? '',
  cast_count:  1,
  guest_count: 1,
  nomination:  props.defaults.nomination ?? '',
  date:        '',
  start_time:  '20:00',
  end_time:    '22:00',
  set_plan:    '1set',
  game_option: '',
  note:        '',
  coupon_id:   null,
})

// ✅ 計算ロジック
const baseRate = 20000
const nominationFee = 2000

const selectedCoupon = computed(() =>
  props.coupons.find(c => c.id === form.coupon_id) || null
)

const total = computed(() => {
  const cast = form.cast_count || 1
  const sets = parseInt(form.set_plan) || 1
  const coupon = selectedCoupon.value?.points || 0
  const hasNomination = form.nomination?.trim().length > 0

 // 時間差を求めて時間単価計算
  const start = form.start_time ? parseInt(form.start_time.split(':')[0]) * 60 + parseInt(form.start_time.split(':')[1]) : 0
  const end   = form.end_time   ? parseInt(form.end_time.split(':')[0]) * 60 + parseInt(form.end_time.split(':')[1])   : 0
  const hours = Math.max(1, Math.ceil((end - start) / 60)) // 少なくとも1時間

  // キャスト人数 × セット数 × 時間 × 2万円
  let price = baseRate * cast * sets * hours
  if (hasNomination) price += nominationFee
  price -= coupon
  if (price < 0) price = 0
  return price
})

// ✅ 送信
const submit = () => {
  if (!confirm(`この内容で予約しますか？\n合計金額：¥${total.value.toLocaleString()}円`)) return
  form.post(route('call.store'))
}
</script>

<template>
  <AppLayout>
    <div class="pt-6 pb-28 px-4 text-white/90 bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%]">

      <h1 class="text-center text-2xl tracking-[0.6em] mb-4">呼　ぶ</h1>
      <div class="h-[2px] w-full bg-gradient-to-r from-[#c8a64a] via-[#e6d08a] to-[#c8a64a] mb-6"></div>

      <form @submit.prevent="submit"
            class="max-w-2xl mx-auto bg-[#2b241b]/60 rounded-lg border border-[#d1b05a]/30 p-5 space-y-5">

        <!-- 場所 -->
        <div class="grid grid-cols-5 items-center gap-3">
          <label class="col-span-2 text-lg">場所</label>
          <input v-model="form.place" type="text"
                 class="col-span-3 h-11 rounded px-3 text-black" />
        </div>
        <p v-if="form.errors.place" class="text-red-300 text-sm -mt-3">
          {{ form.errors.place }}
        </p>

        <!-- キャスト人数 -->
        <div class="grid grid-cols-5 items-center gap-3">
          <label class="col-span-2 text-lg">cast人数</label>
          <div class="col-span-3 flex items-center gap-2">
            <input v-model.number="form.cast_count" type="number" min="1"
                   class="h-11 w-28 rounded px-3 text-black" />
            <span>名</span>
          </div>
        </div>

        <!-- 客人数 -->
        <div class="grid grid-cols-5 items-center gap-3">
          <label class="col-span-2 text-lg">お客様の人数</label>
          <div class="col-span-3 flex items-center gap-2">
            <input v-model.number="form.guest_count" type="number" min="1"
                   class="h-11 w-28 rounded px-3 text-black" />
            <span>名</span>
          </div>
        </div>

        <!-- 指名 -->
        <div class="grid grid-cols-5 items-center gap-3">
          <label class="col-span-2 text-lg">指名</label>
          <input v-model="form.nomination" type="text"
                 class="col-span-3 h-11 rounded px-3 text-black"
                 placeholder="任意（キャスト名など）" />
        </div>

        <!-- 日付 -->
        <div class="grid grid-cols-5 items-center gap-3">
          <label class="col-span-2 text-lg">日</label>
          <input v-model="form.date" type="date"
                 class="col-span-1 h-11 rounded px-3 text-black" />
          <div class="col-span-2"></div>
        </div>

        <!-- 時間 -->
        <div class="grid grid-cols-5 items-center gap-3">
          <label class="col-span-2 text-lg">時間</label>
          <div class="col-span-3 flex items-center gap-2">
            <input v-model="form.start_time" type="time"
                   class="h-11 rounded px-3 text-black" />
            <span>〜</span>
            <input v-model="form.end_time" type="time"
                   class="h-11 rounded px-3 text-black" />
          </div>
        </div>

        <!-- セット数 -->
        <div class="grid grid-cols-5 items-center gap-3">
          <label class="col-span-2 text-lg">セット</label>
          <select v-model="form.set_plan" class="col-span-3 h-11 rounded px-3 text-black">
            <option value="1">1 set</option>
            <option value="2">2 set</option>
            <option value="3">3 set</option>
          </select>
        </div>

        <!-- ゲームオプション -->
        <div class="grid grid-cols-5 items-center gap-3">
          <label class="col-span-2 text-lg">ゲームオプション</label>
          <input v-model="form.game_option" type="text"
                 class="col-span-3 h-11 rounded px-3 text-black"
                 placeholder="例：UNO / 人狼 など" />
        </div>

        <!-- 備考 -->
        <div class="grid grid-cols-5 items-start gap-3">
          <label class="col-span-2 text-lg">備考</label>
          <textarea v-model="form.note" rows="3"
                    class="col-span-3 rounded px-3 py-2 text-black"
                    placeholder="要望や集合場所の詳細など"></textarea>
        </div>

        <!-- クーポン選択 -->
        <div>
          <label class="block text-lg mb-2">クーポンを使用する（任意）</label>
          <div class="grid sm:grid-cols-3 gap-4">
            <label v-for="c in props.coupons" :key="c.id"
                   class="block border rounded-lg p-3 bg-white/80 text-black cursor-pointer hover:ring-2 hover:ring-yellow-400">
              <input type="radio" name="coupon" :value="c.id"
                     v-model="form.coupon_id" class="mr-2" />
              <div class="flex flex-col items-center">
                <img :src="c.image_url" alt=""
                     class="w-24 h-24 object-contain mb-2" />
                <div class="font-semibold">{{ c.name }}</div>
                <div class="text-sm text-gray-600">−{{ c.points }}円</div>
              </div>
            </label>
          </div>
        </div>

        <!-- ✅ 合計金額 -->
        <div class="text-center text-2xl font-bold mt-6 mb-3 text-yellow-300">
          合計金額：￥{{ total.toLocaleString() }} 円
        </div>

        <!-- 予約ボタン -->
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
