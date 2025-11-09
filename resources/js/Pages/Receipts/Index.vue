<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Head, Link } from "@inertiajs/vue3";

const props = defineProps({
  requests: { type: Array, default: () => [] },
});

const isCredit = (r) => r.payment_method === "credit";
</script>

<template>
  <AppLayout>
    <Head title="領収書一覧" />

    <div class="pt-6 pb-20 px-4 text-white/90 bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%]">
      <h1 class="text-center text-2xl tracking-[0.5em] mb-6">領 収 書</h1>

      <div v-if="!props.requests.length" class="text-center text-gray-400">
        リクエスト履歴がありません。
      </div>

      <table v-else class="w-full text-sm bg-white/90 text-black rounded-lg overflow-hidden shadow">
        <thead class="bg-[#caa14b]/70 text-white">
          <tr>
            <th class="p-2 text-left">ID</th>
            <th class="p-2 text-left">日付</th>
            <th class="p-2 text-left">場所</th>
            <th class="p-2 text-left">支払い方法</th>
            <th class="p-2 text-right">金額</th>
            <th class="p-2 text-center">領収書</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in props.requests" :key="r.id" class="border-t">
            <td class="p-2">#{{ r.id }}</td>
            <td class="p-2">{{ r.date }}</td>
            <td class="p-2">{{ r.place || "未設定" }}</td>
            <td class="p-2">{{ r.payment_method === 'credit' ? 'クレジット' : '現金' }}</td>
            <td class="p-2 text-right">¥{{ (r.final_price || r.total_price || 0).toLocaleString() }}</td>
            <td class="p-2 text-center">
              <Link
                v-if="isCredit(r)"
                :href="route('my.receipts.show', r.id)"
                class="px-0 py-1 rounded bg-yellow-400 text-black text-xs font-semibold hover:bg-yellow-300"
              >
                発行
              </Link>
              <span v-else class="text-xs text-gray-400">対象外</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </AppLayout>
</template>
