<!-- resources/js/Pages/Cast/ProfileEdit.vue -->
<script setup>
import AppLayout from "@/Layouts/AppLayout.vue"
import { Head, Link, useForm, router, usePage } from "@inertiajs/vue3"
import { ref, computed } from "vue"

/* ====== props ====== */
const props = defineProps({
  cast: { type: Object, default: null },                 // { id, nickname, ..., photo_path, photos?: [{id,url,sort_order,is_primary}] }
  pendingPermissions: { type: Array, default: () => [] }, // ぼかし解除の承認待ち一覧
  available_tags: { type: Array, default: () => [] }, // [{id,name}]
  pendingPhotoPermissions: { type: Array, default: () => [] },
})

/* ====== 安全な初期化 ====== */
const p = computed(() => props.cast ?? {})

/* tags を配列化（サーバが文字列でも崩れないように） */
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
  // 旧・単発アップロード互換（使わなければ空のまま）
  photo: null,
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

/* 追加 */
const onAddPhotos = (e) => {
  const files = Array.from(e.target.files || [])
  if (!files.length) return
  newFiles.value.push(...files)
  e.target.value = "" // 同じファイル選択で再発火させる
}
/* 並び替え */
const move = (idx, dir) => {
  const to = idx + dir
  if (to < 0 || to >= existing.value.length) return
  const a = existing.value[idx]
  existing.value[idx] = existing.value[to]
  existing.value[to] = a
}
/* メイン */
const setPrimary = (ph) => {
  if (ph._delete) return
  primaryId.value = ph.id
}
/* 削除トグル */
const toggleDelete = (ph) => {
  ph._delete = !ph._delete
  if (ph._delete && primaryId.value === ph.id) primaryId.value = null
}

/* ====== ぼかし解除承認/否認 ====== */
const r = (...args) => (typeof route === "function" ? route(...args) : null)
const approve = (permId) => {
  const id = form.id; if (!id) return
  if (r) router.post(r("casts.unblur.approve", { castProfile: id, permission: permId }), { expires_at: null })
  else   router.post(`/casts/${id}/unblur-requests/${permId}/approve`, { expires_at: null })
}
const deny = (permId) => {
  const id = form.id; if (!id) return
  if (r) router.post(r("casts.unblur.deny", { castProfile: id, permission: permId }))
  else   router.post(`/casts/${id}/unblur-requests/${permId}/deny`)
}

/* ====== 送信 ====== */
const submit = () => {
  form.transform((data) => {
    const fd = new FormData()
    // 基本
    fd.append("nickname", data.nickname ?? "")
    if (data.rank !== "") fd.append("rank", data.rank)
    if (data.age !== "") fd.append("age", data.age)
    if (data.height_cm !== "") fd.append("height_cm", data.height_cm)
    fd.append("cup", data.cup ?? "")
    fd.append("style", data.style ?? "")
    fd.append("alcohol", data.alcohol ?? "")
    fd.append("mbti", (data.mbti ?? "").toString().toUpperCase())
    fd.append("area", data.area ?? "")
    ;
    (data.tag_ids || []).forEach(id => fd.append("tag_ids[]", id))
    fd.append("freeword", data.freeword ?? "")

    // 旧・単発（互換）
    if (data.photo instanceof File) fd.append("photo", data.photo)

    // 追加（複数）
    newFiles.value.forEach(f => fd.append("photos[]", f))

    // 並び（1..N）
    existing.value.forEach((ph, i) => {
      if (!ph.id) return
      fd.append(`orders[${i}][id]`, ph.id)
      fd.append(`orders[${i}][order]`, i + 1)
    })

    // 削除
    existing.value.filter(ph => ph._delete && ph.id).forEach(ph => fd.append("delete_photo_ids[]", ph.id))

    // メイン
    if (primaryId.value) fd.append("primary_photo_id", primaryId.value)

    return fd
  }).post(r ? r("cast.profile.update") : "/cast/profile", {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      newFiles.value = []
      // 反映を強くしたい場合: router.reload({ only: ['cast'] })
    }
  })
}

/* ====== 補助 ====== 
const candidateTags = [
  "ギャル","清楚","アイドル","オタク","可愛い","キレイ","高身長","低身長",
  "スレンダー","細身","グラマー","ロングヘア","ショートヘア","金髪","茶髪","黒髪","明るい","ワイワイ"
]
const toggleTag = (t) => {
  const i = form.tags.indexOf(t)
  if (i >= 0) form.tags.splice(i, 1)
  else form.tags.push(t)
}
  */
const toggleTagId = (id) => {
    const i = form.tag_ids.indexOf(id)
    if (i>=0) form.tag_ids.splice(i,1); else form.tag_ids.push(id)
}

const page = usePage()
const authedUser = computed(() => page.props?.auth?.user ?? null)
// 写真ごとの承認/否認
const approvePhoto = (perm) => {
  const photoId = perm.photo_id
  if (!photoId) return
  if (r) router.post(r("photos.unblur.approve", { castPhoto: photoId, permission: perm.id }), { expires_at: null })
  else   router.post(`/photos/${photoId}/unblur-requests/${perm.id}/approve`, { expires_at: null })
}
const denyPhoto = (perm) => {
  const photoId = perm.photo_id
  if (!photoId) return
  if (r) router.post(r("photos.unblur.deny", { castPhoto: photoId, permission: perm.id }))
  else   router.post(`/photos/${photoId}/unblur-requests/${perm.id}/deny`)
}

/* ====== LINE 連携（通知配信用） ====== */
// props.cast.line_user_id が入っていれば「連携済み」とみなす前提
const line = ref({
  linked: !!(props.cast?.line_user_id),
  displayName: props.cast?.line_display_name ?? null, // サーバで取ってあれば
  userId: props.cast?.line_user_id ?? null,
})

const lineLinking = ref(false)
const lineCode = ref(null)          // 発行した連携コード（ペアリング方式）
const lineBotUrl = ref(null)        // 公式アカウント招待URL（例: https://line.me/R/ti/p/@xxxx）
const lineBotQr  = ref(null)        // 友だち追加QR画像URL（任意）

// 連携コードを発行（サーバ側で一時テーブルに user_id と code を保存し、
// Webhookで code を受け取ったら event.source.userId とひも付ける実装）
const startLineLink = async () => {
  try {
    lineLinking.value = true
    const url = r ? r('line.link.start') : '/line/link/start'
    await router.post(url, {}, {
      preserveScroll: true,
      onSuccess: (page) => {
        const payload = page?.props?.line ?? page?.props?.flash?.line ?? null
        // 返却想定: { code: 'ABC123', bot_url: 'https://...', bot_qr: 'https://...' }
        lineCode.value   = payload?.code ?? null
        lineBotUrl.value = payload?.bot_url ?? null
        lineBotQr.value  = payload?.bot_qr ?? null
      },
    })
  } finally {
    lineLinking.value = false
  }
}

// 連携ステータス確認（Webhook側で code が届いたら DB に line_user_id を保存 → ここで反映）
const checkLineStatus = async () => {
  const url = r ? r('line.link.status') : '/line/link/status'
  await router.get(url, {}, {
    preserveScroll: true,
    onSuccess: (page) => {
      const st = page?.props?.line_status ?? null
      if (st?.linked) {
        line.value.linked = true
        line.value.userId = st.user_id ?? null
        line.value.displayName = st.display_name ?? null
      }
    }
  })
}

// テスト通知
const sendLineTest = async () => {
  const url = r ? r('line.push.test') : '/line/push/test'
  await router.post(url, {}, { preserveScroll: true })
}

// 連携解除
const disconnectLine = async () => {
  const url = r ? r('line.link.disconnect') : '/line/link/disconnect'
  await router.delete(url, { preserveScroll: true, onSuccess: () => {
    line.value = { linked: false, displayName: null, userId: null }
    lineCode.value = null
  }})
}

// クリップボード
const copy = async (text) => {
  try { await navigator.clipboard.writeText(text) } catch {}
}
</script>

<template>
  <AppLayout>
    <Head title="キャストプロフィール編集" />
    <div class="min-h-dvh w-screen flex items-center justify-center bg-black">
      <div class="relative w-full h-dvh md:w-[390px] md:h-[844px] mx-auto
                  bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%] overflow-y-auto">
        <div class="px-6 pt-8 pb-24 text-white/90">

          <h1 class="text-2xl font-semibold mb-6">プロフィール編集</h1>

          <!-- 承認待ち（ぼかし解除） -->
          <div v-if="(pendingPermissions?.length || 0) > 0" class="mb-6">
            <h3 class="font-bold mt-2 mb-2">未処理の閲覧申請</h3>
            <ul class="space-y-2">
              <li v-for="pmt in pendingPermissions" :key="pmt.id" class="p-3 rounded border border-white/20 bg-white/5">
                <div class="text-sm opacity-80">申請者: {{ pmt.viewer.name }} (ID: {{ pmt.viewer.id }})</div>
                <div class="text-sm">メッセージ: {{ pmt.message || "（なし）" }}</div>
                <div class="mt-2 space-x-2">
                  <button @click="approve(pmt.id)" class="bg-green-600 text-white rounded px-3 py-1">承認</button>
                  <button @click="deny(pmt.id)" class="bg-gray-500 text-white rounded px-3 py-1">否認</button>
                </div>
              </li>
            </ul>
          </div>
<!-- ★ 承認待ち（ぼかし解除：写真） -->
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
        <button @click="approvePhoto(perm)" class="bg-green-600 text-white rounded px-3 py-1 text-sm">承認</button>
        <button @click="denyPhoto(perm)"    class="bg-gray-500  text-white rounded px-3 py-1 text-sm">否認</button>
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
          <!-- ============== LINE 通知（登録／連携） ============== -->
<div class="mb-6 p-4 rounded border border-white/20 bg-white/5">
  <div class="flex items-center justify-between mb-2">
    <h3 class="font-semibold">LINEで通知を受け取る</h3>
    <span v-if="line.linked" class="text-xs px-2 py-1 rounded bg-green-600 text-white">
      連携済み
    </span>
  </div>

  <!-- 連携済みビュー -->
  <div v-if="line.linked" class="space-y-2">
    <div class="text-sm opacity-90">
      {{ line.displayName ? `LINE: ${line.displayName}` : 'LINEアカウント連携済み' }}
    </div>
    <div class="flex gap-2">
      <button @click="sendLineTest" class="px-3 py-1 rounded bg-yellow-200 text-black text-sm">テスト通知を送る</button>
      <button @click="disconnectLine" class="px-3 py-1 rounded bg-gray-600 text-white text-sm">連携解除</button>
    </div>
    <p class="text-xs opacity-70">※ ブロックされている場合は送信できません。解除後に再連携が必要です。</p>
  </div>

  <!-- 未連携ビュー -->
  <div v-else class="space-y-3">
    <ol class="list-decimal list-inside space-y-2 text-sm opacity-90">
      <li>下のボタンから <span class="font-semibold">公式アカウントを友だち追加</span> してください。</li>
      <li>「連携コードを発行」を押して表示された <span class="font-semibold">コード</span> を、LINEのトークで送信してください。</li>
      <li>送信後に <span class="font-semibold">「連携を確認」</span> を押すと連携が完了します。</li>
    </ol>

    <div class="flex flex-wrap items-center gap-2">
      <a v-if="lineBotUrl" :href="lineBotUrl" target="_blank"
         class="px-3 py-1 rounded bg-[#06C755] text-white text-sm">友だち追加（LINEを開く）</a>
      <button v-else @click="startLineLink" :disabled="lineLinking"
              class="px-3 py-1 rounded bg-[#06C755] text-white text-sm">
        友だち追加リンクを取得
      </button>

      <button @click="startLineLink" :disabled="lineLinking"
              class="px-3 py-1 rounded bg-yellow-200 text-black text-sm">
        連携コードを発行
      </button>

      <button @click="checkLineStatus"
              class="px-3 py-1 rounded bg-white/10 text-white text-sm">
        連携を確認
      </button>
    </div>

    <div v-if="lineBotQr" class="pt-2">
      <img :src="lineBotQr" class="w-32 h-32 object-contain border border-white/10 rounded" alt="LINE QR" />
      <div class="text-xs opacity-70 mt-1">QRを読み取って友だち追加も可能です。</div>
    </div>

    <div v-if="lineCode" class="p-3 rounded bg-black/50 border border-white/10">
      <div class="text-xs opacity-70 mb-1">あなたの連携コード</div>
      <div class="flex items-center gap-2">
        <code class="text-base tracking-widest">{{ lineCode }}</code>
        <button @click="copy(lineCode)" class="px-2 py-0.5 text-xs rounded bg-white/10">コピー</button>
      </div>
      <div class="text-xs opacity-70 mt-2">※ このコードを、LINEの公式アカウントのトークに送ってください。</div>
    </div>

  </div>
</div>
<!-- ================================================ -->


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
                    <button type="button" @click="move(idx, 1)" class="px-1 py-0.5 text-xs bg-black/50 text-white rounded">↓</button>
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
