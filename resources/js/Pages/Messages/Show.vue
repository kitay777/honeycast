<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { onMounted, nextTick } from 'vue'

const props = defineProps({
  conversation_id: { type: Number, required: true },
  messages: { type: Array, default: () => [] }, // [{id,me,name,body,image_path,at}]
})

const form = useForm({ body: '', image: null })
const send = () => form.post(`/messages/${props.conversation_id}`, { onSuccess: () => {
  form.reset('body','image')
}})

let scroller
onMounted(()=> { scroller = document.getElementById('chat-scroll'); nextTick(()=>scroller?.scrollTo(0, scroller.scrollHeight)) })
</script>

<template>
  <AppLayout>
    <div class="pt-6 pb-28 px-4 text-white/90
                bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%]">

      <div id="chat-scroll" class="h-[60vh] overflow-y-auto rounded border border-white/10 p-3 bg-black/20">
        <div v-for="m in props.messages" :key="m.id" class="mb-3 flex"
             :class="m.me ? 'justify-end' : 'justify-start'">
          <div class="max-w-[70%] rounded px-3 py-2"
               :class="m.me ? 'bg-[#6b4b17] text-yellow-100' : 'bg-white/80 text-black'">
            <div v-if="m.body" class="whitespace-pre-wrap">{{ m.body }}</div>
            <img v-if="m.image_path" :src="`/storage/${m.image_path}`" class="mt-1 rounded" />
            <div class="text-[10px] opacity-70 text-right mt-1">{{ m.at }}</div>
          </div>
        </div>
      </div>

      <form @submit.prevent="send" class="mt-3 flex items-center gap-2">
        <input v-model="form.body" type="text" placeholder="メッセージを入力"
               class="flex-1 h-11 rounded px-3 text-black" />
        <input type="file" @change="e=>form.image=e.target.files[0]" class="text-xs" />
        <button :disabled="form.processing"
                class="h-11 px-4 rounded bg-yellow-400 text-black font-semibold">送信</button>
      </form>
    </div>
  </AppLayout>
</template>
