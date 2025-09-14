<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { computed } from 'vue'
const props = defineProps({
  shop:Object, invites:Array, members:Object, captureUrl:String
})
const rows  = computed(()=> props.members?.data ?? [])
const links = computed(()=> props.members?.links ?? [])
function createInvite(){ router.post('/my/invites', {}, { preserveScroll:true }) }
function qrSrc(inv){ return `/my/invites/${inv.id}/qr.png` }
function landingUrl(inv){ return props.captureUrl.replace('TOKEN_SAMPLE', inv.token) }
</script>

<template>
  <AppLayout>
  <Head :title="`My Shop | ${shop?.name}`" />
  <div class="max-w-screen-sm mx-auto p-4  text-black">
    <h1 class="text-2xl font-semibold mb-3">{{ shop?.name }} の招待 & メンバー</h1>

    <!-- 招待QR -->
    <div class="bg-white rounded-2xl shadow p-4 mb-4">
      <div class="flex items-center justify-between mb-2">
        <div class="font-medium">招待QR</div>
        <button @click="createInvite" class="px-3 py-2 rounded bg-black text-white text-sm">＋ 新規発行</button>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div v-for="inv in invites" :key="inv.id" class="border rounded-xl p-2">
          <img :src="qrSrc(inv)" class="w-full rounded-lg" alt="QR">
          <div class="mt-1 text-[10px] break-all text-gray-500">{{ landingUrl(inv) }}</div>
          <div class="text-[11px] mt-1">利用 {{ inv.used_count }} 回</div>
        </div>
      </div>
    </div>

    <!-- メンバー -->
    <div class="bg-white rounded-2xl shadow p-4 text-black">
      <div class="font-medium mb-2">登録メンバー</div>
      <ul class="divide-y">
        <li v-for="m in rows" :key="m.id" class="py-2">
          <div class="font-medium">{{ m.name }}</div>
          <div class="text-xs text-gray-500">{{ m.email }}</div>
        </li>
      </ul>
      <div class="mt-3 flex gap-2 flex-wrap">
        <Link v-for="(lnk,i) in links" :key="i" :href="lnk.url || '#'"
              class="px-3 py-1 border rounded"
              :class="[lnk.active ? 'bg-black text-white' : '', !lnk.url ? 'opacity-50 pointer-events-none' : '']"
              v-html="lnk.label" />
      </div>
    </div>
  </div>
  </AppLayout>
</template>
