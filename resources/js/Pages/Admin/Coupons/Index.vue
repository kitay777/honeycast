<script setup>
import { Head, Link, router } from "@inertiajs/vue3"
import AdminLayout from "@/Layouts/AdminLayout.vue"

defineProps({ coupons: Object })

function destroyCoupon(id) {
  if (!confirm("削除しますか？")) return
  router.delete(`/admin/coupons/${id}`)
}

function deleteCoupon(id) {
  if (confirm("本当に削除しますか？")) {
    router.delete(`/admin/coupons/${id}`, {
      onSuccess: () => alert("✅ クーポンを削除しました！"),
      onError: () => alert("削除に失敗しました。"),
    })
  }
}
</script>

<template>
  <Head title="クーポン管理" />
  <AdminLayout active-key="coupons">
    <template #header>
      <div class="flex justify-between px-5 py-3 bg-white border-b">
        <h1 class="text-xl font-semibold">クーポン管理</h1>
        <Link
          href="/admin/coupons/create"
          class="px-3 py-1 bg-black text-white rounded hover:bg-gray-800"
        >
          ＋ 新規作成
        </Link>
      </div>
    </template>

    <div class="p-6 bg-gray-50 min-h-screen">
      <table class="w-full text-sm bg-white rounded shadow border">
        <thead class="bg-gray-100 text-gray-700">
          <tr>
            <th class="p-2 text-left w-24">画像</th>
            <th class="p-2 text-left">コード</th>
            <th class="p-2 text-left">名称</th>
            <th class="p-2 text-center w-24">ポイント</th>
            <th class="p-2 text-center w-40">有効期限</th>
            <th class="p-2 text-center w-20">状態</th>
            <th class="p-2 text-right w-32">操作</th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="c in coupons.data"
            :key="c.id"
            class="border-t hover:bg-gray-50 transition"
          >
            <!-- 画像 -->
            <td class="p-2">
              <img
                v-if="c.image_url"
                :src="c.image_url"
                class="w-16 h-16 object-cover rounded border"
                alt="coupon"
              />
              <div
                v-else
                class="w-16 h-16 rounded border flex items-center justify-center text-gray-400 text-xs bg-gray-50"
              >
                No Image
              </div>
            </td>

            <!-- コード -->
            <td class="p-2 font-mono text-[13px] text-gray-800">{{ c.code }}</td>

            <!-- 名称 -->
            <td class="p-2 text-gray-800">
              {{ c.name }}
            </td>

            <!-- ポイント -->
            <td class="p-2 text-center font-semibold text-pink-600">
              {{ c.discount_points }}
            </td>

            <!-- 有効期限 -->
            <td class="p-2 text-center">
              <span
                v-if="c.expires_at"
                :class="[
                  new Date(c.expires_at) < new Date() ? 'text-red-500' : 'text-gray-700',
                ]"
              >
                {{ c.expires_at }}
              </span>
              <span v-else class="text-gray-400">—</span>
            </td>

            <!-- 状態 -->
            <td class="p-2 text-center">
              <span
                class="px-2 py-0.5 rounded text-xs font-semibold"
                :class="c.active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-500'"
              >
                {{ c.active ? '有効' : '無効' }}
              </span>
            </td>

            <!-- 操作 -->
            <td class="p-2 text-right">
              <Link
                :href="`/admin/coupons/${c.id}/edit`"
                class="text-blue-600 hover:underline mr-3"
                >編集</Link
              >
              <button
                @click="destroyCoupon(c.id)"
                class="text-red-600 hover:underline"
              >
                削除
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- ページネーション -->
      <div
        v-if="coupons.links?.length"
        class="mt-6 flex flex-wrap gap-2 justify-center text-sm"
      >
        <Link
          v-for="lnk in coupons.links"
          :key="lnk.label"
          :href="lnk.url || '#'"
          v-html="lnk.label"
          class="px-3 py-1 border rounded"
          :class="[
            lnk.active ? 'bg-black text-white' : '',
            !lnk.url ? 'opacity-50 pointer-events-none' : '',
          ]"
        />
      </div>
    </div>
  </AdminLayout>
</template>
