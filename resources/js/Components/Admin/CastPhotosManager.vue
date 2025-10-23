<script setup>
import { ref, onMounted } from 'vue'

const props = defineProps({
  castId: { type: [Number, String], required: true },
})

const loading = ref(false)
const photos  = ref([])  // {id,url,caption,should_blur,is_primary,sort_order}
const files   = ref([])  // ローカル選択

async function fetchList() {
  loading.value = true
  try {
    const res = await fetch(`/admin/casts/${props.castId}/photos`, { headers: { Accept:'application/json' }})
    photos.value = await res.json()
  } finally {
    loading.value = false
  }
}

onMounted(fetchList)

function onFileChange(e){
  files.value = Array.from(e.target.files ?? [])
}

async function uploadAll(){
  if (!files.value.length) return
  const fd = new FormData()
  files.value.forEach(f => fd.append('photos[]', f))
  // 必要なら一括ブラー指定: fd.append('should_blur','1')
  const res = await fetch(`/admin/casts/${props.castId}/photos`, { method:'POST', body: fd })
  const json = await res.json()
  photos.value = json.photos
  files.value = []
}

async function setPrimary(p){
  await fetch(`/admin/casts/${props.castId}/photos/${p.id}`, {
    method:'PUT',
    headers: { 'Content-Type':'application/json' },
    body: JSON.stringify({ is_primary: !p.is_primary }),
  })
  await fetchList()
}

async function toggleBlur(p){
  await fetch(`/admin/casts/${props.castId}/photos/${p.id}`, {
    method:'PUT',
    headers: { 'Content-Type':'application/json' },
    body: JSON.stringify({ should_blur: !p.should_blur }),
  })
  p.should_blur = !p.should_blur
}

async function saveCaption(p){
  await fetch(`/admin/casts/${props.castId}/photos/${p.id}`, {
    method:'PUT',
    headers: { 'Content-Type':'application/json' },
    body: JSON.stringify({ caption: p.caption ?? '' }),
  })
}

async function removePhoto(p){
  if(!confirm('この写真を削除しますか？')) return
  await fetch(`/admin/casts/${props.castId}/photos/${p.id}`, { method:'DELETE' })
  photos.value = photos.value.filter(x => x.id !== p.id)
}

/* ---- 並び替え（ネイティブD&D） ---- */
let dragIndex = null
function onDragStart(i){ dragIndex = i }
function onDragOver(e){ e.preventDefault() }
async function onDrop(i){
  if (dragIndex === null || dragIndex === i) return
  const moved = photos.value.splice(dragIndex,1)[0]
  photos.value.splice(i,0,moved)
  dragIndex = null
  // id 配列を送って確定
  const order = photos.value.map(p => p.id)
  await fetch(`/admin/casts/${props.castId}/photos/reorder`, {
    method:'PATCH',
    headers: { 'Content-Type':'application/json' },
    body: JSON.stringify({ order }),
  })
}
</script>

<template>
  <div class="space-y-3">
    <div class="flex items-center gap-3">
      <input type="file" multiple accept="image/*" @change="onFileChange" />
      <button class="px-3 py-2 rounded bg-black text-white disabled:opacity-50"
              :disabled="!files.length" @click="uploadAll">
        画像をアップロード
      </button>
      <span v-if="files.length" class="text-xs text-gray-600">
        選択中: {{ files.length }} 件
      </span>
    </div>

    <div v-if="loading" class="text-sm text-gray-500">読み込み中…</div>
    <div v-else-if="!photos.length" class="text-sm text-gray-500">まだ写真がありません。</div>

    <ul v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
      <li v-for="(p,i) in photos" :key="p.id"
          class="rounded-xl border overflow-hidden bg-white"
          draggable="true"
          @dragstart="onDragStart(i)"
          @dragover="onDragOver"
          @drop="onDrop(i)">
        <div class="relative">
          <img :src="p.url" class="w-full h-40 object-cover" :class="p.should_blur ? 'blur-sm' : ''" />
          <button class="absolute top-2 left-2 text-xs px-2 py-1 rounded bg-white/80"
                  :class="p.is_primary ? 'border border-yellow-500 text-yellow-600' : 'border'"
                  @click="setPrimary(p)">
            ★ メイン
          </button>
          <button class="absolute top-2 right-2 text-xs px-2 py-1 rounded bg-white/80 border"
                  @click="removePhoto(p)">
            削除
          </button>
        </div>
        <div class="p-2 space-y-2">
          <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" :checked="p.should_blur" @change="toggleBlur(p)" />
            ぼかし
          </label>
          <input type="text" v-model="p.caption" placeholder="キャプション（任意）"
                 class="w-full border rounded px-2 py-1 text-sm"
                 @keyup.enter="saveCaption(p)" @blur="saveCaption(p)" />
        </div>
      </li>
    </ul>
  </div>
</template>
