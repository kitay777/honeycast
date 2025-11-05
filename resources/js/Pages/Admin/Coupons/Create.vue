<!-- resources/js/Pages/Admin/Coupons/Create.vue -->
<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref } from "vue"; // ✅ これを追加
import AdminLayout from "@/Layouts/AdminLayout.vue";


const form = useForm({
  code: "",
  name: "",
  discount_points: 100,
  max_uses: null,
  expires_at: "",
  active: true,
  image: null,
});

const previewUrl = ref(null);
function onImageChange(e) {
  const file = e.target.files[0];
  if (file) {
    form.image = file;
    previewUrl.value = URL.createObjectURL(file);
  }
}

function submit() {
  form.post("/admin/coupons", {
    forceFormData: true,
    onSuccess: () => {
      alert("✅ クーポンを作成しました");
    },
  });
}
</script>

<template>
  <Head title="クーポン新規作成" />
  <AdminLayout active-key="coupons">
    <template #header>
      <div class="flex justify-between px-5 py-3 bg-white border-b">
        <h1 class="text-xl font-semibold">クーポン新規作成</h1>
        <Link href="/admin/coupons" class="text-blue-600">一覧へ戻る</Link>
      </div>
    </template>

    <div class="p-6 bg-gray-50 min-h-screen">
      <form
        @submit.prevent="submit"
        enctype="multipart/form-data"
        class="bg-white shadow rounded-xl border p-6 max-w-lg mx-auto space-y-5"
      >
        <div>
          <label class="block text-sm font-semibold mb-1">クーポンコード</label>
          <input
            v-model="form.code"
            type="text"
            class="w-full border rounded px-3 py-2"
            placeholder="例: WELCOME100"
            required
          />
        </div>

        <div>
          <label class="block text-sm font-semibold mb-1">名称</label>
          <input
            v-model="form.name"
            type="text"
            class="w-full border rounded px-3 py-2"
            placeholder="例: 初回限定100ptクーポン"
            required
          />
        </div>

        <div>
          <label class="block text-sm font-semibold mb-1">クーポン画像</label>
          <input type="file" accept="image/*" @change="onImageChange" />
          <div v-if="previewUrl" class="mt-2">
            <img
              :src="previewUrl"
              class="w-40 h-40 object-contain border rounded"
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-semibold mb-1">付与ポイント</label>
          <input
            v-model.number="form.discount_points"
            type="number"
            min="1"
            class="w-full border rounded px-3 py-2"
            required
          />
        </div>

        <div>
          <label class="block text-sm font-semibold mb-1">有効期限</label>
          <input
            v-model="form.expires_at"
            type="date"
            class="w-full border rounded px-3 py-2"
          />
        </div>

        <div class="flex items-center gap-2">
          <input
            v-model="form.active"
            type="checkbox"
            id="active"
            class="w-4 h-4"
          />
          <label for="active" class="text-sm">有効にする</label>
        </div>

        <div class="pt-4 flex gap-3 justify-end">
          <Link
            href="/admin/coupons"
            class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300"
          >
            キャンセル
          </Link>
          <button
            type="submit"
            class="px-4 py-2 bg-black text-white rounded hover:brightness-110"
            :disabled="form.processing"
          >
            登録する
          </button>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>
