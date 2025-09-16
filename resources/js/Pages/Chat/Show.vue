<script setup>
import { ref, onMounted, nextTick, computed, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({ thread: Object, messages: Array })

const page = usePage()
const currentUserId = computed(() => page?.props?.auth?.user?.id ?? null)

const FOOTER_H = 56
const COMPOSER_H = 64

const listRef = ref(null)
const textareaRef = ref(null)   // ← 追加：フォーカス復帰用
const body = ref('')
const sending = ref(false)

const scrollToBottom = () => {
  const el = listRef.value
  if (!el) return
  // 連続で呼んで描画完了を待つ
  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      // スムーズに飛ばしたければ下の行を有効化
      // el.scrollTo({ top: el.scrollHeight, behavior: 'smooth' })
      el.scrollTop = el.scrollHeight
    })
  })
}

const doSubmit = () => {
  const text = body.value
  if (!text.trim() || sending.value) return
  sending.value = true

  const tempId = `tmp-${Date.now()}`
  props.messages.push({
    id: tempId,
    chat_thread_id: props.thread.id,
    sender_id: currentUserId.value,
    body: text,
    created_at: new Date().toISOString(),
    _pending: true,
  })
  nextTick(scrollToBottom)

  router.post(`/chat/${props.thread.id}/messages`, { body: text }, {
    preserveScroll: true,
    onSuccess: () => {
      body.value = ''                 // ← 成功時にクリア
      nextTick(() => textareaRef.value?.focus())  // ← フォーカス戻す
    },
    onError: () => {
      const i = props.messages.findIndex(m => m.id === tempId)
      if (i >= 0) props.messages.splice(i, 1)
    },
    onFinish: () => {
      sending.value = false
      nextTick(scrollToBottom)
    },
  })
}

/** キーダウン：CR=改行, Shift+CR=送信, IME 変換中は反応しない */
const onKeyDown = (e) => {
  // 日本語入力の変換中は keydown を無視
  if (e.isComposing) return

  if (e.key === 'Enter') {
    if (e.shiftKey) {
      e.preventDefault()  // テキストエリアの改行を止めて送信
      doSubmit()
    } 
    // Shift なしの Enter は改行させる（何もしない）
  }
}

onMounted(() => {
  nextTick(() => {
    scrollToBottom()
    textareaRef.value?.focus()
    nextTick(scrollToBottom)
  })

  if (window.Echo) {
    window.Echo.private(`chat.thread.${props.thread.id}`)
      .listen('.message.created', (payload) => {
        // 楽観追加の置換 or 追記
        const tmpIdx = props.messages.findIndex(
          m => String(m.id).startsWith('tmp-') && m.sender_id === payload.sender_id
        )
        if (tmpIdx >= 0) props.messages[tmpIdx] = payload
        else if (!props.messages.some(m => m.id === payload.id)) props.messages.push(payload)

        nextTick(scrollToBottom)
      })
  }
})

watch(
  () => props.messages.length,
  () => nextTick(scrollToBottom)
)
</script>

<template>
  <AppLayout>
    <div
      class="relative mx-auto max-w-[780px] min-h-[100dvh] flex flex-col text-black bg-transparent"
      :style="{ paddingBottom: `calc(${FOOTER_H}px + ${COMPOSER_H}px + env(safe-area-inset-bottom, 0px))` }"
    >
      <!-- リスト（背景透明） -->
      <div ref="listRef" class="flex-1 overflow-y-auto bg-transparent scroll-smooth">
        <div class="p-3 space-y-2">
          <div v-for="m in props.messages" :key="m.id"
               class="flex"
               :class="m.sender_id === currentUserId ? 'justify-end' : 'justify-start'">
            <div class="px-3 py-2 rounded-lg border max-w-[80%]"
                 :class="m.sender_id === currentUserId ? 'bg-emerald-50' : 'bg-white/90'">
              <div class="whitespace-pre-wrap break-words">{{ m.body }}</div>
              <div class="mt-1 text-[10px] text-gray-500">
                {{ new Date(m.created_at).toLocaleString() }}
                <span v-if="m._pending" class="ml-1 opacity-60">(送信中…)</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 入力バー（フッターの上に固定） -->
      <div
        class="fixed left-0 right-0 z-50 border-t"
        :class="'bg-white/95 backdrop-blur-sm'"
        :style="{ bottom: `calc(${FOOTER_H}px + env(safe-area-inset-bottom, 0px))` }"
      >
        <div class="mx-auto max-w-[780px] p-2">
          <form @submit.prevent="doSubmit" class="flex gap-2 items-end">
            <textarea
              ref="textareaRef"
              v-model="body"
              @keydown="onKeyDown"
              rows="2"
              class="flex-1 border rounded p-2 text-black placeholder:text-gray-400"
              placeholder="メッセージを入力（Shift+Enterで送信／Enterで改行）"
            ></textarea>
            <button
              type="submit"
              :disabled="sending || !body.trim()"
              class="px-4 py-2 rounded bg-black text-white disabled:opacity-50"
            >
              送信
            </button>
          </form>

        </div>
      </div>
    </div>
  </AppLayout>
</template>
