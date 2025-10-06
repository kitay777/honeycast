<script setup>
import { onMounted, ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
  liffId:   { type: String, default: '' },
  botUrl:   { type: String, default: '' },
  redirect: { type: String, default: '/cast/profile/edit' },
})

const page = usePage()
const csrf = computed(() => page.props?.value?.csrf ?? '')

const status  = ref('初期化中…')
const loading = ref(true)
const needFriend = ref(false)

/** 動的にLIFF SDKを読み込む */
const loadScript = (src) => new Promise((resolve, reject) => {
  const s = document.createElement('script'); s.src = src; s.async = true
  s.onload = resolve; s.onerror = reject; document.head.appendChild(s)
})

const register = async () => {
  try {
    if (!props.liffId) {
      status.value = 'LIFF IDが未設定です'; return
    }
    await loadScript('https://static.line-scdn.net/liff/edge/2/sdk.js')

    await window.liff.init({ liffId: props.liffId })

    // LINE外ならLINEで開く（戻ってくる）
    if (!window.liff.isInClient() && !window.liff.isLoggedIn()) {
      location.href = `https://liff.line.me/${props.liffId}`; return
    }
    if (!window.liff.isLoggedIn()) window.liff.login()

    status.value = '友だち状態を確認中…'
    let friendFlag = false
    try {
      const fr = await window.liff.getFriendship()
      friendFlag = !!fr.friendFlag
    } catch { friendFlag = false }

    if (!friendFlag) {
      needFriend.value = true
      status.value = '公式アカウントを友だち追加してください。'
      loading.value = false
      return
    }

    status.value = 'LINE情報を取得中…'
    const prof = await window.liff.getProfile()

    status.value = '登録しています…'
    const res = await fetch('/register/line/direct', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf.value || '',  // ← CSRF
      },
      credentials: 'include',
      body: JSON.stringify({
        uid: prof.userId,
        displayName: prof.displayName,
        redirect: props.redirect,
      }),
    })
    const json = await res.json().catch(()=>({}))
    if (!res.ok || !json.ok) {
      needFriend.value = true
      status.value = '登録に失敗しました。友だち追加の状態を確認してください。'
      loading.value = false
      return
    }

    status.value = '登録完了。遷移します…'
    location.href = json.redirect || props.redirect || '/'
  } catch (e) {
    console.error(e)
    status.value = 'エラー: ' + (e?.message || e)
    loading.value = false
  }
}

onMounted(register)
const goFriend = () => { if (props.botUrl) window.open(props.botUrl, '_blank', 'noopener'); }
const retry    = () => location.reload()
</script>

<template>
  <div class="min-h-dvh bg-black text-white flex items-center justify-center p-5">
    <div class="max-w-md w-full bg-[#111] border border-[#333] rounded-2xl p-5">
      <h1 class="text-xl font-semibold mb-2">LINEでかんたん登録</h1>
      <p class="text-sm opacity-80">{{ status }}</p>

      <div v-if="needFriend" class="mt-4 space-y-2">
        <button v-if="botUrl" @click="goFriend"
          class="w-full h-11 rounded-lg bg-[#06C755] text-white font-medium">友だち追加</button>
        <button @click="retry" class="w-full h-11 rounded-lg bg-yellow-200 text-black font-medium">
          追加したので進む
        </button>
      </div>

      <div v-else class="mt-4">
        <div class="h-10 flex items-center text-sm opacity-70"
             v-if="loading">処理中…</div>
      </div>
    </div>
  </div>
</template>
