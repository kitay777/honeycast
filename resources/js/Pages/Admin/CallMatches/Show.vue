<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, Link } from "@inertiajs/vue3";

const props = defineProps({
  match: Object,
  extensions: Array,
});
const match = props.match;
</script>

<template>
  <Head :title="`コール詳細 #${match.id}`" />
  <AdminLayout active-key="call-matches">
    <template #header>
      <div class="px-5 py-3 bg-white border-b flex justify-between items-center">
        <h1 class="text-xl font-semibold">📞 コール詳細 #{{ match.id }}</h1>
        <Link
          href="/admin/call-matches"
          class="text-sm text-blue-600 hover:underline"
        >
          ← 戻る
        </Link>
      </div>
    </template>

    <div class="p-6 bg-gray-50 min-h-screen">
      <div class="bg-white rounded-2xl shadow p-6 max-w-3xl mx-auto space-y-6">
        <!-- ステータス -->
        <div class="flex justify-between items-center border-b pb-3">
          <div class="text-lg font-semibold">
            ステータス：
            <span
              :class="match.status === 'ended'
                ? 'text-red-600'
                : 'text-emerald-600'"
            >
              {{ match.status }}
            </span>
          </div>
          <div class="text-sm text-gray-500">
            ID: {{ match.id }}
          </div>
        </div>

        <!-- キャスト情報 -->
        <section>
          <h2 class="font-semibold text-gray-700 mb-1">キャスト</h2>
          <div class="p-3 bg-gray-50 rounded-lg border">
            <div class="text-lg font-medium">
              {{ match.cast_profile?.nickname || "(未設定)" }}
            </div>
            <div class="text-sm text-gray-500">
              {{ match.cast_profile?.user?.email || "-" }}
            </div>
            <div class="text-sm text-gray-500 mt-1">
              ID: {{ match.cast_profile?.id }}
            </div>
          </div>
        </section>

        <!-- ユーザー情報 -->
        <section>
          <h2 class="font-semibold text-gray-700 mb-1">ユーザー</h2>
          <div class="p-3 bg-gray-50 rounded-lg border">
            <div class="text-lg font-medium">
              {{ match.call_request?.user?.name || "(不明)" }}
            </div>
            <div class="text-sm text-gray-500">
              {{ match.call_request?.user?.email || "-" }}
            </div>
          </div>
        </section>

        <!-- 時間情報 -->
        <section>
          <h2 class="font-semibold text-gray-700 mb-1">時間情報</h2>
          <table class="text-sm w-full border">
            <tbody>
              <tr class="border-t">
                <th class="p-2 bg-gray-50 text-left w-40">開始時間</th>
                <td class="p-2">{{ match.started_at || "—" }}</td>
              </tr>
              <tr class="border-t">
                <th class="p-2 bg-gray-50 text-left">終了時間</th>
                <td class="p-2">{{ match.ended_at || "—" }}</td>
              </tr>
              <tr class="border-t">
                <th class="p-2 bg-gray-50 text-left">合計時間</th>
                <td class="p-2">{{ (match.duration_minutes / 60).toFixed(1) }} 時間</td>
              </tr>
            </tbody>
          </table>
        </section>

        <!-- 延長履歴 -->
        <section v-if="extensions?.length">
          <h2 class="font-semibold text-gray-700 mb-1">延長履歴</h2>
          <ul class="divide-y text-sm border rounded">
            <li
              v-for="(e, i) in extensions"
              :key="i"
              class="flex justify-between items-center p-2"
            >
              <span>+{{ e.minutes }} 分</span>
              <span class="text-gray-500">{{ e.updated_at }}</span>
            </li>
          </ul>
        </section>

        <!-- 位置情報 -->
        <section>
          <h2 class="font-semibold text-gray-700 mb-1">位置情報</h2>
          <div v-if="match.latitude" class="space-y-1">
            <a
              :href="`https://www.google.com/maps?q=${match.latitude},${match.longitude}`"
              target="_blank"
              class="text-blue-600 underline text-sm"
            >
              Googleマップで開く
            </a>
            <div class="text-sm text-gray-600">
              緯度: {{ match.latitude }} / 経度: {{ match.longitude }}
            </div>
          </div>
          <div v-else class="text-gray-400 text-sm">— 記録なし —</div>
        </section>
      </div>
    </div>
  </AdminLayout>
</template>
