<!-- resources/js/Pages/Cast/ProfileEdit.vue -->
<script setup>
import AppLayout from "@/Layouts/AppLayout.vue"
import { Head, Link, useForm, router, usePage } from "@inertiajs/vue3"
import { ref, computed } from "vue"
const LIFF_ID = (import.meta.env?.VITE_LIFF_ID ?? '').toString()

/** route() が無い/解決失敗でもフォールバックURLで必ず動かす */
const urlFor = (name, params = {}, fallback = "") => {
  try {
    if (typeof route === "function") {
      const u = route(name, params)
      if (typeof u === "string" && u.length) return u
    }
  } catch {}
  return fallback
}

/* ====== props ====== */
const props = defineProps({
  cast: { type: Object, default: null },
  pendingPermissions: { type: Array, default: () => [] },
  available_tags: { type: Array, default: () => [] },
  pendingPhotoPermissions: { type: Array, default: () => [] },
})

/* ====== 安全な初期化 ====== */
const p = computed(() => props.cast ?? {})

/* tags 初期化（サーバが文字列でも安全） */
const initialTags = Array.isArray(p.value?.tags)
  ? p.value.tags
  : (p.value?.tags ? String(p.value.tags).split(/[\s,，、]+/).filter(Boolean) : [])

/* ====== プロフィール基本フォーム ====== */
const form = useForm({
  id: p.value?.id ?? null,
  nickname: p.value?.nickname ?? "",
  rank: p.value?.rank ?? "",
  age: p.value?.age ?? "",
  height_cm: p.value?.height_cm ?? "",
  cup: p.value?.cup ?? "",
  style: p.value?.style ?? "",
  alcohol: p.value?.alcohol ?? "",
  mbti: p.value?.mbti ?? "",
  area: p.value?.area ?? "",
  tag_ids: Array.isArray(props.cast?.tag_ids) ? [...props.cast.tag_ids] : [],
  freeword: p.value?.freeword ?? "",
  photo: null, // 旧・単発互換
})

/* ====== 写真管理（複数） ====== */
const existing = ref(
  (p.value?.photos ?? []).map(ph => ({
    id: ph.id,
    url: ph.url ?? (ph.path ? `/storage/${ph.path}` : null),
    sort_order: ph.sort_order ?? 0,
    is_primary: !!ph.is_primary,
    _delete: false
  }))
)
const primaryId = ref(existing.value.find(x => x.is_primary)?.id || null)
const newFiles = ref([])

/* 安全なプレビューURL生成/解放 */
const getPreviewUrl = (file) => {
  try {
    if (typeof file === 'object' && file && (file instanceof Blob || file instanceof File)) {
      const URL_ = (globalThis?.URL || window?.URL || self?.URL)
      return URL_?.createObjectURL ? URL_.createObjectURL(file) : ''
    }
  } catch (_) {}
  return ''
}
const revokePreviewUrl = (src) => {
  try {
    const URL_ = (globalThis?.URL || window?.URL || self?.URL)
    URL_?.revokeObjectURL?.(src)
  } catch (_) {}
}

/* 追加/並び替え/メイン/削除 */
const onAddPhotos = (e) => {
  const files = Array.from(e.target.files || [])
  if (!files.length) return
  newFiles.value.push(...files)
  e.target.value = ""
}
const move = (idx, dir) => {
  const to = idx + dir
  if (to < 0 || to >= existing.value.length) return
  const a = existing.value[idx]
  existing.value[idx] = existing.value[to]
  existing.value[to] = a
}
const setPrimary = (ph) => {
  if (ph._delete) return
  primaryId.value = ph.id
}
const toggleDelete = (ph) => {
  ph._delete = !ph._delete
  if (ph._delete && primaryId.value === ph.id) primaryId.value = null
}

/* ====== ぼかし解除承認/否認 ====== */
const approve = (permId) => {
  const id = form.id; if (!id) return
  const url = urlFor('casts.unblur.approve', { castProfile: id, permission: permId }, `/casts/${id}/unblur-requests/${permId}/approve`)
  router.post(url, { expires_at: null })
}
const deny = (permId) => {
  const id = form.id; if (!id) return
  const url = urlFor('casts.unblur.deny', { castProfile: id, permission: permId }, `/casts/${id}/unblur-requests/${permId}/deny`)
  router.post(url)
}

/* ====== 送信 ====== */
const submit = () => {
  form.transform((data) => {
    const fd = new FormData()
    fd.append("nickname", data.nickname ?? "")
    if (data.rank !== "") fd.append("rank", data.rank)
    if (data.age !== "") fd.append("age", data.age)
    if (data.height_cm !== "") fd.append("height_cm", data.height_cm)
    fd.append("cup", data.cup ?? "")
    fd.append("style", data.style ?? "")
    fd.append("alcohol", data.alcohol ?? "")
    fd.append("mbti", (data.mbti ?? "").toString().toUpperCase())
    fd.append("area", data.area ?? "")
    ;(data.tag_ids || []).forEach(id => fd.append("tag_ids[]", id))
    fd.append("freeword", data.freeword ?? "")

    if (data.photo instanceof File) fd.append("photo", data.photo)
    newFiles.value.forEach(f => fd.append("photos[]", f))

    existing.value.forEach((ph, i) => {
      if (!ph.id) return
      fd.append(`orders[${i}][id]`, ph.id)
      fd.append(`orders[${i}][order]`, i + 1)
    })
    existing.value.filter(ph => ph._delete && ph.id).forEach(ph => fd.append("delete_photo_ids[]", ph.id))
    if (primaryId.value) fd.append("primary_photo_id", primaryId.value)
    return fd
  }).post(urlFor('cast.profile.update', {}, '/cast/profile'), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => { newFiles.value = [] }
  })
}

/* ====== タグ ====== */
const toggleTagId = (id) => {
  const i = form.tag_ids.indexOf(id)
  if (i>=0) form.tag_ids.splice(i,1); else form.tag_ids.push(id)
}

/* ====== ページ/ユーザー ====== */
const page = usePage()
const authedUser = computed(() => page.props?.value?.auth?.user ?? null)

/* ====== 写真ごとの承認/否認 ====== */
const approvePhoto = (perm) => {
  const photoId = perm.photo_id
  if (!photoId) return
  const url = urlFor('photos.unblur.approve', { castPhoto: photoId, permission: perm.id }, `/photos/${photoId}/unblur-requests/${perm.id}/approve`)
  router.post(url, { expires_at: null })
}
const denyPhoto = (perm) => {
  const photoId = perm.photo_id
  if (!photoId) return
  const url = urlFor('photos.unblur.deny', { castPhoto: photoId, permission: perm.id }, `/photos/${photoId}/unblur-requests/${perm.id}/deny`)
  router.post(url)
}

/* ====== LINE 連携（リンクは常時表示） ====== */
const line = ref({
  linked: !!(props.cast?.line_user_id),
  displayName: props.cast?.line_display_name ?? null,
  userId: props.cast?.line_user_id ?? null,
})

const lineLinking = ref(false)
const lineCode = ref(null)
const lineBotUrl = ref(null)
const lineBotQr  = ref(null)

/* サーバ共有 line_env、Vite 変数でフォールバック */
const envBotUrl = computed(() => page.props?.value?.line_env?.bot_url ?? null)
const envBotQr  = computed(() => page.props?.value?.line_env?.bot_qr  ?? null)
const viteBotUrl = import.meta.env.VITE_LINE_BOT_ADD_URL || null
const viteBotQr  = import.meta.env.VITE_LINE_BOT_QR || null
/* 実使用URL/QR（応答URL → line_env → Vite） */
const addUrl = computed(() => lineBotUrl.value || envBotUrl.value || viteBotUrl)
const addQr  = computed(() => lineBotQr.value  || envBotQr.value  || viteBotQr)

/** 連携コード発行（POSTのみ。開くのは上のリンクが担当） */
const startLineLink = async () => {
  if (lineLinking.value) return
  lineLinking.value = true
  try {
    const url = urlFor('line.link.start', {}, '/line/link/start')
    await router.post(url, {}, {
      preserveScroll: true,
      onSuccess: (inertiaPage) => {
        // まず flash で受け取る（入っていれば即反映）
        const props = inertiaPage?.props ?? page.props?.value ?? {}
        const payload = props.line ?? props.flash?.line ?? null
        if (payload) {
          lineCode.value   = payload?.code ?? null
          lineBotUrl.value = payload?.bot_url ?? null
          lineBotQr.value  = payload?.bot_qr ?? null
        }
      },
      onError: (errors) => {
        console.error('[line.link.start] failed', errors)
        alert('連携コードの発行に失敗しました（ルート未定義/CSRF/権限など）。')
      },
      onFinish: async () => {
        // ★ 最終的に peek で“必ず”最新を反映（flashが無くてもOK）
        await refreshLineCode()
      },
    })
  } finally {
    lineLinking.value = false
  }
}


const refreshLineCode = async () => {
  try {
    const url = urlFor('line.link.peek', {}, '/line/link/peek')
    // Inertiaのrouterでも良いですが、素のfetchが手軽です
    const res = await fetch(url, { credentials: 'include' })
    if (!res.ok) return
    const json = await res.json()
    lineCode.value   = json?.code ?? null
    lineBotUrl.value = json?.bot_url ?? null
    lineBotQr.value  = json?.bot_qr ?? null
  } catch (e) { console.error('refreshLineCode failed', e) }
}

const checkLineStatus = async () => {
  const url = urlFor('line.link.status', {}, '/line/link/status')
  await router.get(url, {}, {
    preserveScroll: true,
    onSuccess: (resp) => {
      const st = resp?.props?.line_status ?? page.props?.value?.line_status ?? null
      if (st?.linked) {
        line.value.linked = true
        line.value.userId = st.user_id ?? null
        line.value.displayName = st.display_name ?? null
      }
    }
  })
}

const sendLineTest = async () => {
  const url = urlFor('line.push.test', {}, '/line/push/test')
  await router.post(url, {}, {
    preserveScroll: true,
    onSuccess: (inertiaPage) => {
      const props = inertiaPage?.props ?? page.props?.value ?? {}
      const ok = props.flash?.success ?? props.success ?? null
      const err = props.flash?.error ?? props.error ?? null
      if (ok) alert(ok)
      if (err) alert(err)
    },
    onError: () => alert('テスト通知の送信に失敗しました'),
  })
}

const disconnectLine = async () => {
  const url = urlFor('line.link.disconnect', {}, '/line/link/disconnect')
  await router.delete(url, {
    preserveScroll: true,
    onSuccess: () => {
      line.value = { linked: false, displayName: null, userId: null }
      lineCode.value = null
      lineBotUrl.value = null
      lineBotQr.value  = null
    }
  })
}

/* クリップボード */
const copy = async (text) => {
  try { await navigator.clipboard.writeText(text) } catch {}
}
</script>

<template>
  <AppLayout>
    <Head title="キャストプロフィール編集" />
    <!-- ★ Safari 対策：外側は単純なflex、内側に max-h-dvh + min-h-0 + overflow-y-auto -->
    <div class="min-h-dvh w-screen bg-black flex justify-center md:py-6">
      <div
        class="relative w-full max-w-[390px] max-h-dvh mx-auto
               bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%]
               overflow-y-auto min-h-0">
        <div class="px-6 py-8 text-white/90">

          <h1 class="text-2xl font-semibold mb-6">プロフィール編集</h1>

          <!-- 承認待ち（ぼかし解除） -->
          <div v-if="(pendingPermissions?.length || 0) > 0" class="mb-6">
            <h3 class="font-bold mt-2 mb-2">未処理の閲覧申請</h3>
            <ul class="space-y-2">
              <li v-for="pmt in pendingPermissions" :key="pmt.id" class="p-3 rounded border border-white/20 bg-white/5">
                <div class="text-sm opacity-80">申請者: {{ pmt.viewer.name }} (ID: {{ pmt.viewer.id }})</div>
                <div class="text-sm">メッセージ: {{ pmt.message || "（なし）" }}</div>
                <div class="mt-2 space-x-2">
                  <button type="button" @click="approve(pmt.id)" class="bg-green-600 text-white rounded px-3 py-1">承認</button>
                  <button type="button" @click="deny(pmt.id)" class="bg-gray-500 text-white rounded px-3 py-1">否認</button>
                </div>
              </li>
            </ul>
          </div>

          <!-- 承認待ち（写真） -->
          <div v-if="(pendingPhotoPermissions?.length || 0) > 0" class="mb-6">
            <h3 class="font-bold mt-2 mb-2">未処理の閲覧申請（写真）</h3>
            <ul class="grid grid-cols-1 gap-3">
              <li v-for="perm in pendingPhotoPermissions" :key="perm.id"
                  class="p-3 rounded border border-white/20 bg-white/5 flex items-center gap-3">
                <img v-if="perm.thumb" :src="perm.thumb" class="w-20 h-14 object-cover rounded" />
                <div class="flex-1">
                  <div class="text-sm">
                    申請者: <span class="opacity-90">{{ perm.viewer?.name }} (ID: {{ perm.viewer?.id }})</span>
                  </div>
                  <div class="text-xs opacity-80">メッセージ: {{ perm.message || '（なし）' }}</div>
                  <div class="text-[11px] opacity-60">申請日時: {{ perm.created_at }}</div>
                </div>
                <div class="shrink-0 space-x-2">
                  <button type="button" @click="approvePhoto(perm)" class="bg-green-600 text-white rounded px-3 py-1 text-sm">承認</button>
                  <button type="button" @click="denyPhoto(perm)"    class="bg-gray-500  text-white rounded px-3 py-1 text-sm">否認</button>
                </div>
              </li>
            </ul>
            <div class="mt-1 text-xs opacity-70">
              ※ 写真の承認は、その写真だけ非ぼかし表示になります（プロフィール全体の許可とは独立）。
            </div>
          </div>

          <!-- スケジュール・その他リンク -->
          <div class="mb-4 flex flex-wrap gap-4 items-center">
            <Link v-if="form.id" :href="`/casts/${form.id}/schedule`" class="text-sm underline text-yellow-200">
              ● スケジュール編集へ
            </Link>
            <Link href="/tweets" class="text-sm underline text-yellow-200">
              ● ツイート
            </Link>
            <Link href="/logout" method="post" as="button" class="text-sm underline text-yellow-200">
              ● ログアウト
            </Link>
          </div>

          <!-- ============== LINE 連携 ============== -->
          <div class="mb-6 p-4 rounded border border-white/20 bg-white/5">
            <div class="flex items-center justify-between mb-2">
              <h3 class="font-semibold">LINEで通知を受け取る</h3>
              <span v-if="line.linked" class="text-xs px-2 py-1 rounded bg-green-600 text-white">
                連携済み
              </span>
            </div>

            <!-- 連携済み -->
            <div v-if="line.linked" class="space-y-2">
              <div class="text-sm opacity-90">
                {{ line.displayName ? `LINE: ${line.displayName}` : 'LINEアカウント連携済み' }}
              </div>
              <div class="flex gap-2">
                <button type="button" @click="sendLineTest" class="px-3 py-1 rounded bg-yellow-200 text-black text-sm">テスト通知を送る</button>
                <button type="button" @click="disconnectLine" class="px-3 py-1 rounded bg-gray-600 text-white text-sm">連携解除</button>
              </div>
              <p class="text-xs opacity-70">※ ブロックされている場合は送信できません。解除後に再連携が必要です。</p>
            </div>

            <!-- 未連携 -->
            <div v-else class="space-y-3">
              <ol class="list-decimal list-inside space-y-2 text-sm opacity-90">
                <li>下のボタンから <span class="font-semibold">公式アカウントを友だち追加</span> してください。</li>
                <li>「連携コードを発行」を押して表示された <span class="font-semibold">コード</span> を、LINEのトークで送信してください。</li>
                <li>送信後に <span class="font-semibold">「連携を確認」</span> を押すと連携が完了します。</li>
              </ol>

              <div class="flex flex-wrap items-center gap-2">
                <!-- 初期表示から必ず開ける（応答URL→line_env→Vite） 
                <a v-if="!line.linked && LIFF_ID"
                   :href="`https://liff.line.me/${LIFF_ID}`"
                   class="px-3 py-1 rounded bg-[#06C755] text-white text-sm">
                  LINEで即連携（ワンタップ）
                </a>
                -->
                <!-- 連携コード発行（POSTのみ。開くのは上のリンクが担当） -->
                <button type="button"
                        @click="startLineLink"
                        :disabled="lineLinking"
                        class="px-3 py-1 rounded bg-yellow-200 text-black text-sm">
                  連携コードを発行
                </button>

                <button type="button"
                        @click="checkLineStatus"
                        class="px-3 py-1 rounded bg-white/10 text-white text-sm">
                  連携を確認
                </button>
              </div>

              <div v-if="addQr" class="pt-2">
                <img :src="addQr" class="w-32 h-32 object-contain border border-white/10 rounded" alt="LINE QR" />
                <div class="text-xs opacity-70 mt-1">QRを読み取って友だち追加も可能です。</div>
              </div>

              <div v-if="lineCode" class="p-3 rounded bg-black/50 border border-white/10">
                <div class="text-xs opacity-70 mb-1">あなたの連携コード</div>
                <div class="flex items-center gap-2">
                  <code class="text-base tracking-widest">{{ lineCode }}</code>
                  <button type="button" @click="copy(lineCode)" class="px-2 py-0.5 text-xs rounded bg-white/10">コピー</button>
                </div>
                <div class="text-xs opacity-70 mt-2">※ このコードを、LINEの公式アカウントのトークに送ってください。</div>
              </div>
            </div>
          </div>
          <!-- ===================================== -->

          <!-- 基本プロフィール -->
          <form @submit.prevent="submit" class="space-y-5">
            <div>
              <label class="block mb-1 text-sm">ニックネーム</label>
              <input v-model="form.nickname" type="text" class="w-full h-11 rounded-md px-3 text-black" />
              <p v-if="form.errors.nickname" class="text-xs text-red-300 mt-1">{{ form.errors.nickname }}</p>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block mb-1 text-sm">ランク</label>
                <input v-model.number="form.rank" type="number" min="0" max="99" class="w-full h-11 rounded-md px-3 text-black" />
                <p v-if="form.errors.rank" class="text-xs text-red-300 mt-1">{{ form.errors.rank }}</p>
              </div>
              <div>
                <label class="block mb-1 text-sm">年齢</label>
                <input v-model.number="form.age" type="number" min="18" max="99" class="w-full h-11 rounded-md px-3 text-black" />
                <p v-if="form.errors.age" class="text-xs text-red-300 mt-1">{{ form.errors.age }}</p>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block mb-1 text-sm">身長(cm)</label>
                <input v-model.number="form.height_cm" type="number" min="120" max="220" class="w-full h-11 rounded-md px-3 text-black" />
                <p v-if="form.errors.height_cm" class="text-xs text-red-300 mt-1">{{ form.errors.height_cm }}</p>
              </div>
              <div>
                <label class="block mb-1 text-sm">カップ</label>
                <input v-model="form.cup" type="text" placeholder="A〜H等" class="w-full h-11 rounded-md px-3 text-black" />
                <p v-if="form.errors.cup" class="text-xs text-red-300 mt-1">{{ form.errors.cup }}</p>
              </div>
            </div>

            <div>
              <label class="block mb-1 text-sm">エリア</label>
              <select v-model="form.area" class="w-full h-11 rounded-md px-3 text-black">
                <option value="">選択してください</option>
                <option>北海道・東北</option><option>関東</option><option>中部</option>
                <option>近畿</option><option>中国・四国</option><option>九州・沖縄</option>
              </select>
              <p v-if="form.errors.area" class="text-xs text-red-300 mt-1">{{ form.errors.area }}</p>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block mb-1 text-sm">スタイル</label>
                <select v-model="form.style" class="w-full h-11 rounded-md px-3 text-black">
                  <option value="">未選択</option>
                  <option>スレンダー</option><option>細身</option><option>グラマー</option><option>その他</option>
                </select>
              </div>
              <div>
                <label class="block mb-1 text-sm">お酒</label>
                <select v-model="form.alcohol" class="w-full h-11 rounded-md px-3 text-black">
                  <option value="">未選択</option>
                  <option>飲む</option><option>少し</option><option>飲まない</option>
                </select>
              </div>
            </div>

            <div>
              <label class="block mb-1 text-sm">MBTI</label>
              <input v-model="form.mbti" maxlength="4" placeholder="ENFPなど" class="w-full h-11 rounded-md px-3 text-black uppercase" />
              <p v-if="form.errors.mbti" class="text-xs text-red-300 mt-1">{{ form.errors.mbti }}</p>
            </div>

            <div>
              <label class="block mb-2 text-sm">タグ</label>
              <div class="flex flex-wrap gap-2">
                <button v-for="t in available_tags" :key="t.id" type="button"
                        @click="toggleTagId(t.id)"
                        :class="form.tag_ids.includes(t.id) ? 'bg-yellow-200 text-black' : 'bg-white/20'"
                        class="px-3 py-1 rounded-full text-sm">
                        {{ t.name }}
                </button>
              </div>
            </div>

            <div>
              <label class="block mb-1 text-sm">自己紹介</label>
              <textarea v-model="form.freeword" rows="4" class="w-full rounded-md px-3 py-2 text-black"></textarea>
              <p v-if="form.errors.freeword" class="text-xs text-red-300 mt-1">{{ form.errors.freeword }}</p>
            </div>

            <!-- =============== 写真管理（複数） =============== -->
            <div class="pt-4 space-y-3">
              <div class="flex items-center justify-between">
                <h3 class="font-semibold">写真</h3>
                <label class="px-3 py-1 rounded bg-yellow-200 text-black cursor-pointer text-sm">
                  追加
                  <input type="file" accept="image/*" multiple class="hidden" @change="onAddPhotos" />
                </label>
              </div>

              <div v-if="existing.length" class="grid grid-cols-3 gap-3">
                <div v-for="(ph, idx) in existing" :key="ph.id" class="relative border border-white/20 rounded overflow-hidden">
                  <img :src="ph.url" class="w-full h-28 object-cover" />
                  <div class="absolute top-1 left-1 flex gap-1">
                    <button type="button" @click="move(idx,-1)" class="px-1 py-0.5 text-xs bg-black/50 text-white rounded">↑</button>
                    <button type="button" @click="move(idx, 1)" class="px-1 py-0.5 text-xs bg-black/50 text白 rounded">↓</button>
                  </div>
                  <div class="absolute top-1 right-1">
                    <button type="button"
                            :class="['px-1 py-0.5 text-xs rounded', ph.id===primaryId && !ph._delete ? 'bg-amber-400 text-black' : 'bg-black/50 text-white']"
                            @click="setPrimary(ph)">★</button>
                  </div>
                  <button type="button"
                          class="absolute bottom-1 right-1 px-1.5 py-0.5 text-xs bg-red-600 text-white rounded"
                          @click="toggleDelete(ph)">
                    {{ ph._delete ? '復活' : '削除' }}
                  </button>
                </div>
              </div>

              <div v-if="newFiles.length" class="mt-2">
                <div class="text-sm opacity-80 mb-1">追加予定（保存で反映）</div>
                <div class="flex flex-wrap gap-3">
                  <div v-for="(f,i) in newFiles" :key="i" class="w-24 h-24 border border-white/20 rounded overflow-hidden">
                    <img :src="getPreviewUrl(f)" class="w-full h-full object-cover" @load="revokePreviewUrl($event.target.src)" />
                  </div>
                </div>
              </div>
            </div>
            <!-- ============================================== -->

            <div class="pt-4">
              <button :disabled="form.processing"
                      class="w-full h-12 rounded-md font-bold tracking-[0.5em] bg-[#7a560f] text-white border border-[#c79a2b] shadow hover:brightness-110">
                更　新
              </button>
            </div>
          </form>

        </div>
      </div>
    </div>
  </AppLayout>
</template>
