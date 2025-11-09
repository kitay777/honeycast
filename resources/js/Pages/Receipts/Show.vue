<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Head } from "@inertiajs/vue3";

const props = defineProps({ req: Object });
</script>

<template>
  <AppLayout>
    <Head title="領収書" />
    <div class="min-h-dvh py-10 px-6 bg-white text-black max-w-lg mx-auto shadow rounded-lg">
      <h1 class="text-xl font-bold text-center mb-4">領 収 書</h1>
      <p class="text-sm text-right text-gray-600">発行日: {{ new Date(props.req.executed_at).toLocaleDateString() }}</p>
      <div class="border-t mt-4 pt-4 space-y-2 text-sm">
        <p>リクエストID：#{{ props.req.id }}</p>
        <p>日付：{{ props.req.date }} {{ props.req.start_time }}〜{{ props.req.end_time }}</p>
        <p>場所：{{ props.req.place }}</p>
        <p>支払い方法：クレジット</p>
        <p class="text-lg font-semibold text-right">合計金額：¥{{ props.req.final_price?.toLocaleString() ?? 0 }}</p>
      </div>

      <div class="mt-6 text-center text-xs text-gray-500">
        ※この領収書は電子発行です。印刷してご利用いただけます。
      </div>
    </div>
  </AppLayout>
</template>
