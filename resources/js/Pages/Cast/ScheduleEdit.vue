<!-- resources/js/Pages/Cast/ScheduleEdit.vue -->
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  castId: { type: Number, required: true },
  days:   { type: Array,  default: () => [] }, // [{date:'2025-08-28', slots:[{start:'20:00', end:'00:00'}]}]
})

const form = useForm({
  days: JSON.parse(JSON.stringify(props.days ?? [])), // deep copy
})

const addSlot = (i) => {
  form.days[i].slots = form.days[i].slots || []
  form.days[i].slots.push({ start: '20:00', end: '00:00' })
}
const removeSlot = (i, j) => {
  form.days[i].slots.splice(j, 1)
}

const submit = () => {
  form.post(`/casts/${props.castId}/schedule`) // Ziggy無しでOK
}
</script>

<template>
  <AppLayout>
    <div class="pt-6 pb-28 px-4 text-white/90
                bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%]">

      <h1 class="text-xl font-semibold mb-4">スケジュール編集（直近1週間）</h1>

      <form @submit.prevent="submit" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
          <div v-for="(d, i) in form.days" :key="d.date"
               class="bg-[#2b241b]/60 rounded border border-[#d1b05a]/30 p-3">
            <div class="font-medium mb-2">{{ d.date }}</div>

            <div v-if="d.slots?.length" class="space-y-2">
              <div v-for="(s, j) in d.slots" :key="j" class="flex items-center gap-2">
                <input type="time" v-model="s.start" class="text-black rounded px-2 py-1 h-9" />
                <span>〜</span>
                <input type="time" v-model="s.end" class="text-black rounded px-2 py-1 h-9" />
                <button type="button" @click="removeSlot(i, j)"
                        class="px-2 py-1 rounded bg-red-500 text-white text-xs">削除</button>
              </div>
            </div>
            <div v-else class="text-sm opacity-70 mb-2">枠なし</div>

            <button type="button" @click="addSlot(i)"
                    class="mt-2 px-3 py-1 rounded bg-yellow-200 text-black text-sm">＋枠を追加</button>
          </div>
        </div>

        <div class="pt-2">
          <button type="submit" :disabled="form.processing"
                  class="w-full md:w-64 h-12 rounded bg-[#6b4b17] border border-[#d1b05a] text-yellow-200 font-bold">
            保存する
          </button>
          <div v-if="form.errors && Object.keys(form.errors).length" class="mt-2 text-red-300 text-sm">
            入力エラーがあります。時刻形式(HH:MM)をご確認ください。
          </div>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
