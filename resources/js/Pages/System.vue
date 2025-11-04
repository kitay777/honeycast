<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, onMounted } from "vue";
import { Link } from "@inertiajs/vue3";

const activeTab = ref("個別チョコ");
const contents = ref({
  individual: "",
  group: "",
  chokkotto: "",
  delivery: "",
});

onMounted(async () => {
  const res = await fetch("/api/system-texts");
  if (res.ok) contents.value = await res.json();
});
</script>

<template>
  <AppLayout>
    <div class="pt-6 pb-28 px-6 text-white/90 text-lg bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%]">
      <h1 class="text-left text-3xl tracking-[0.5em] mb-10">～ SYSTEM ～</h1>

      <!-- タブ -->
      <div class="flex flex-wrap gap-3 mb-8">
        <button
          v-for="tab in [
            { key: '個別チョコ', label: '個別チョコ（個別1：1）' },
            { key: '団体チョコ', label: '団体チョコ（団体利用）' },
            { key: 'チョ個っと', label: 'チョ個っと（日中限定）' },
            { key: 'チョコデリ', label: 'チョコデリ（通常デリヘル）' },
          ]"
          :key="tab.key"
          @click="activeTab = tab.key"
          class="px-6 py-3 rounded-full text-base font-bold shadow transition"
          :class="activeTab === tab.key
              ? 'bg-gradient-to-r from-[#caa14b] to-[#f0e1b1] text-black'
              : 'bg-gray-700 text-white'"
        >
          {{ tab.label }}
        </button>
      </div>

      <!-- コンテンツ -->
      <div v-html="contents[activeTab]" class="bg-black/60 p-6 rounded-2xl leading-relaxed space-y-4 text-left"></div>

      <!-- ボタン -->
      <div class="flex flex-col items-center gap-3 mt-12">
        <Link
          :href="route('call.create')"
          class="px-14 py-4 rounded-full text-xl bg-gradient-to-r from-[#caa14b] to-[#f0e1b1] text-black font-bold tracking-[0.5em] shadow"
        >
          予約する
        </Link>

        <Link
          :href="route('games.index')"
          class="px-14 py-4 rounded-full text-xl bg-gradient-to-r from-[#6b7280] to-[#a1a1aa] text-white font-bold tracking-[0.5em] shadow"
        >
          ゲームを見る
        </Link>
      </div>
    </div>
  </AppLayout>
</template>
