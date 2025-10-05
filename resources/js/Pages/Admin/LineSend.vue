<script setup>
import { Head, useForm, usePage, router, Link } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

/** route() が無い/解決失敗でもフォールバックする */
const urlFor = (name, params = {}, fallback = "") => {
  try { if (typeof route === 'function') { const u = route(name, params); if (typeof u === 'string' && u.length) return u } } catch {}
  return fallback
}

const page = usePage()
const props = page.props
const user  = computed(() => props.value?.user ?? {})
const tokenExists = computed(() => !!props.value?.tokenExists)

const flash = computed(() => props.value?.flash ?? {})

/** 個別送信フォーム */
const form = useForm({
  text: '',
  notification_disabled: false,
})
const sending = ref(false)
const sendOne = async () => {
  if (sending.value) return
  sending.value = true
  try {
    const url = urlFor('admin.users.line.push', { user: user.value.id }, `/admin/users/${user.value.id}/line/push`)
    await form.post(url, {
      preserveScroll: true,
      onFinish: () => { sending.value = false },
      onSuccess: () => { form.reset('text') },
    })
  } finally {
    sending.value = false
  }
}

/** 一括送信（任意） */
const bulkIdsRaw = ref('')
const bulkForm = useForm({
  user_ids: [],
  text: '',
  notification_disabled: false,
})
const sendBulk = async () => {
  bulkForm.user_ids = (bulkIdsRaw.value || '')
    .split(',').map(s => s.trim()).filter(Boolean).map(n => parseInt(n, 10))
    .filter(n => Number.isInteger(n))
  const url = urlFor('admin.line.multicast', {}, '/admin/line/multicast')
  await bulkForm.post(url, { preserveScroll: true })
}
</script>

<template>
  <Head title="LINE送信（管理）" />
  <div class="max-w-3xl mx-auto p-6">
    <h1 class="text-2xl font-semibold mb-4">LINE送信（管理）</h1>

    <div v-if="flash.success" class="mb-3 rounded bg-green-100 text-green-800 px-3 py-2">{{ flash.success }}</div>
    <div v-if="flash.error"   class="mb-3 rounded bg-red-100   text-red-800   px-3 py-2 whitespace-pre-line">{{ flash.error }}</div>

    <div class="rounded border border-gray-200 p-4 mb-8">
      <h2 class="font-semibold mb-2">個別送信</h2>
      <div class="text-sm text-gray-600 mb-2">
        ユーザー: <span class="font-medium">{{ user.name }}</span>（ID: {{ user.id }}）
        <span class="ml-2">連携:
          <span :class="user.line_user_id ? 'text-green-600' : 'text-red-600'">
            {{ user.line_user_id ? '済' : '未' }}
          </span>
        </span>
        <span class="ml-2">トークン: <span :class="tokenExists ? 'text-green-600' : 'text-red-600'">{{ tokenExists ? 'OK' : '未設定' }}</span></span>
      </div>

      <div class="space-y-2">
        <label class="block text-sm">メッセージ</label>
        <textarea v-model="form.text" rows="5" class="w-full rounded border px-3 py-2" placeholder="送信内容（最大1000文字）"></textarea>

        <label class="inline-flex items-center gap-2 text-sm">
          <input type="checkbox" v-model="form.notification_disabled">
          通知を鳴らさない
        </label>

        <div class="mt-2">
          <button
            type="button"
            @click="sendOne"
            :disabled="sending || !user.line_user_id || !tokenExists || !form.text"
            class="px-4 py-2 rounded text-white"
            :class="(sending || !user.line_user_id || !tokenExists || !form.text) ? 'bg-gray-400' : 'bg-emerald-600 hover:brightness-110'">
            送信
          </button>
        </div>
      </div>
    </div>

    <!-- 任意：一括送信 -->
    <div class="rounded border border-gray-200 p-4">
      <details>
        <summary class="cursor-pointer select-none font-semibold">一括送信（任意）</summary>
        <div class="mt-3 space-y-2">
          <label class="block text-sm">ユーザーID（カンマ区切り）</label>
          <input v-model="bulkIdsRaw" type="text" class="w-full rounded border px-3 py-2" placeholder="例）1,2,3">

          <label class="block text-sm">メッセージ</label>
          <textarea v-model="bulkForm.text" rows="4" class="w-full rounded border px-3 py-2"></textarea>

          <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" v-model="bulkForm.notification_disabled">
            通知を鳴らさない
          </label>

          <div class="mt-2">
            <button type="button" @click="sendBulk"
              :disabled="!bulkIdsRaw || !bulkForm.text"
              class="px-4 py-2 rounded text-white"
              :class="(!bulkIdsRaw || !bulkForm.text) ? 'bg-gray-400' : 'bg-gray-800 hover:brightness-110'">
              一括送信
            </button>
          </div>
        </div>
      </details>
    </div>
  </div>
</template>
