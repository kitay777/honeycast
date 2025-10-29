<script setup>
import { ref, onMounted } from "vue";

const props = defineProps({
  castId: [Number, String],
});

const photos = ref([]);
const loading = ref(false);
const uploading = ref(false);
const newFiles = ref([]);
const shouldBlur = ref(false);

// ✅ CSRFトークン
const csrf = document
  .querySelector('meta[name="csrf-token"]')
  ?.getAttribute("content");

// 共通fetch（FormData対応）
async function csrfFetch(url, options = {}) {
  const headers = {};
  if (!(options.body instanceof FormData)) {
    headers["Content-Type"] = "application/json";
  }
  headers["X-CSRF-TOKEN"] = csrf;

  const merged = {
    credentials: "same-origin",
    headers,
    ...options,
  };

  return await fetch(url, merged);
}

// 一覧取得
async function loadPhotos() {
  loading.value = true;
  const res = await fetch(`/admin/casts/${props.castId}/photos`, {
    headers: { Accept: "application/json" },
    credentials: "same-origin",
  });
  photos.value = await res.json();
  loading.value = false;
}

// ✅ ファイル選択イベント
function onFileChange(e) {
  const files = Array.from(e.target.files || []);
  newFiles.value = files;
  // Vue reactivity 対応 → 手動で更新を発火
  if (files.length > 0) console.log("選択されたファイル数:", files.length);
}

// ✅ アップロード
async function upload() {
  if (!newFiles.value.length) return;
  uploading.value = true;
  const fd = new FormData();
  for (const f of newFiles.value) fd.append("photos[]", f);
  fd.append("should_blur", shouldBlur.value ? "1" : "0");

  const res = await csrfFetch(`/admin/casts/${props.castId}/photos`, {
    method: "POST",
    body: fd,
  });

  if (res.ok) {
    await loadPhotos();
    newFiles.value = [];
  } else {
    const txt = await res.text();
    console.error("Upload failed:", txt);
    alert("アップロードに失敗しました");
  }
  uploading.value = false;
}

// ✅ 削除
async function remove(p) {
  if (!confirm("この写真を削除しますか？")) return;
  const res = await csrfFetch(`/admin/casts/${props.castId}/photos/${p.id}`, {
    method: "DELETE",
  });
  if (res.ok) await loadPhotos();
}

// ✅ 主設定
async function setPrimary(p) {
  const res = await csrfFetch(`/admin/casts/${props.castId}/photos/${p.id}`, {
    method: "PUT",
    body: JSON.stringify({ is_primary: true }),
  });
  if (res.ok) await loadPhotos();
}

// ✅ ぼかしトグル
async function toggleBlur(p) {
  const res = await csrfFetch(`/admin/casts/${props.castId}/photos/${p.id}`, {
    method: "PUT",
    body: JSON.stringify({ should_blur: !p.should_blur }),
  });
  if (res.ok) await loadPhotos();
}

// ✅ 並び替え：上下ボタンで並べ替え
async function move(p, dir) {
  const idx = photos.value.findIndex((x) => x.id === p.id);
  if (idx < 0) return;
  const newIdx = idx + dir;
  if (newIdx < 0 || newIdx >= photos.value.length) return;

  // 配列上で入れ替え
  const arr = [...photos.value];
  const tmp = arr[idx];
  arr[idx] = arr[newIdx];
  arr[newIdx] = tmp;
  photos.value = arr;

  // サーバーへ並び順送信
  const order = photos.value.map((x) => x.id);
  const res = await csrfFetch(`/admin/casts/${props.castId}/photos/reorder`, {
    method: "PATCH",
    body: JSON.stringify({ order }),
  });
  if (res.ok) await loadPhotos();
}

onMounted(loadPhotos);
</script>

<template>
  <div class="space-y-4">
    <div v-if="loading" class="text-gray-500">読み込み中...</div>

    <div
      v-else
      class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4"
    >
      <div
        v-for="p in photos"
        :key="p.id"
        class="relative border rounded-xl overflow-hidden shadow hover:shadow-lg transition"
      >
        <img
          :src="p.url"
          class="object-cover w-full h-36"
          :class="{ 'blur-sm': p.should_blur }"
        />
        <div
          class="absolute top-0 left-0 right-0 bg-black/40 text-white text-xs flex justify-between px-1"
        >
          <span>#{{ p.sort_order }}</span>
          <span v-if="p.is_primary" class="text-yellow-300 font-bold">★</span>
        </div>
        <div
          class="absolute bottom-0 left-0 right-0 bg-black/50 text-white text-xs flex justify-center gap-2 py-0.5"
        >
          <button @click="setPrimary(p)">主</button>
          <button @click="toggleBlur(p)">
            {{ p.should_blur ? "明" : "ぼ" }}
          </button>
          <button @click="move(p, -1)">↑</button>
          <button @click="move(p, 1)">↓</button>
          <button @click="remove(p)">削</button>
        </div>
      </div>
    </div>

    <!-- アップロード -->
    <div class="border-t pt-3">
      <label class="block text-sm font-semibold mb-1">新しい写真を追加</label>
      <div class="flex items-center gap-2 flex-wrap">
        <input type="file" multiple @change="onFileChange" class="text-sm" />
        <label class="flex items-center gap-1 text-sm cursor-pointer">
          <input type="checkbox" v-model="shouldBlur" />
          <span>ぼかしを付けて追加</span>
        </label>
        <button
          @click="upload"
          :disabled="uploading || !newFiles.length"
          class="px-3 py-1 bg-blue-600 text-white text-sm rounded disabled:opacity-50"
        >
          {{ uploading ? "アップロード中..." : "画像をアップロード" }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.blur-sm {
  filter: blur(4px);
}
</style>
