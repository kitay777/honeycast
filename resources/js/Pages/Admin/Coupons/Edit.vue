<!-- resources/js/Pages/Admin/Coupons/Edit.vue -->
<script setup>
import { ref } from "vue";
import { Head, useForm, Link } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";

const props = defineProps({
  coupon: Object,
});

/**
 * ✅ useForm 初期値
 */
const form = useForm({
  _method: "PUT", // ← ★ Laravel に「PUT」リクエストとして認識させる
  code: props.coupon.code ?? "",
  name: props.coupon.name ?? "",
  discount_points: props.coupon.discount_points ?? 0,
  max_uses: props.coupon.max_uses ?? "",
  expires_at: props.coupon.expires_at ?? "",
  active: props.coupon.active ?? false,
  image: null,
});

const previewUrl = ref(props.coupon.image_url ?? null);

function onImageChange(e) {
  const file = e.target.files[0];
  if (file) {
    form.image = file;
    previewUrl.value = URL.createObjectURL(file);
  }
}

/**
 * ✅ 更新ボタン動作
 */
function submit() {
  // Laravel の FormData が空になる問題を防ぐため「POST」で送る
  form.post(`/admin/coupons/${props.coupon.id}`, {
    forceFormData: true, // ← FormData 強制変換（画像対応）
    onSuccess: () => {
      alert("✅ クーポンを更新しました！");
    },
    onError: (errors) => {
      console.error("💥 更新エラー:", errors);
      alert("更新に失敗しました。入力内容を確認してください。");
    },
  });
}
</script>




<template>
  <Head title="クーポン編集" />
  <AdminLayout active-key="coupons">
    <!-- ✅ ヘッダー -->
    <template #header>
      <div class="flex justify-between px-5 py-3 bg-white border-b">
        <h1 class="text-xl font-semibold">クーポン編集</h1>
        <Link href="/admin/coupons" class="text-blue-600 hover:underline">
          一覧へ戻る
        </Link>
      </div>
    </template>

    <!-- ✅ 編集フォーム -->
    <div class="p-6 bg-gray-50 min-h-screen">
      <form
        @submit.prevent="submit"
        enctype="multipart/form-data"
        class="bg-white shadow rounded-xl border p-6 max-w-lg mx-auto space-y-5"
      >
        <!-- クーポンコード -->
        <div>
          <label class="block text-sm font-semibold mb-1">クーポンコード</label>
          <input
            v-model="form.code"
            type="text"
            class="w-full border rounded px-3 py-2"
            required
          />
          <div v-if="form.errors.code" class="text-red-500 text-sm mt-1">
            {{ form.errors.code }}
          </div>
        </div>

        <!-- 名称 -->
        <div>
          <label class="block text-sm font-semibold mb-1">名称</label>
          <input
            v-model="form.name"
            type="text"
            class="w-full border rounded px-3 py-2"
            required
          />
        </div>

        <!-- 画像 -->
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

        <!-- ポイント -->
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

        <!-- 有効期限 -->
        <div>
          <label class="block text-sm font-semibold mb-1">有効期限</label>
          <input
            v-model="form.expires_at"
            type="date"
            class="w-full border rounded px-3 py-2"
          />
        </div>

        <!-- 有効/無効 -->
        <div class="flex items-center gap-2">
          <input
            v-model="form.active"
            type="checkbox"
            id="active"
            class="w-4 h-4"
          />
          <label for="active" class="text-sm">有効にする</label>
        </div>

        <!-- ボタン -->
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
            更新する
          </button>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>
