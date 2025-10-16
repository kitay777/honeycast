<!-- resources/js/Pages/Contact/Pending.vue -->
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  supportEmail: { type: String, default: 'support@example.com' },
  message: { type: String, default: 'ただいまお問い合わせフォームを準備中です。' },
})
</script>

<template>
  <AppLayout>
    <div class="min-h-dvh px-4 py-10 text-white/90
                bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%]">
      <div class="max-w-xl mx-auto text-center">
        <div class="inline-flex items-center justify-center h-12 w-12 rounded-full bg-white/10 border border-white/20 mb-4">
          <svg viewBox="0 0 24 24" class="h-6 w-6 animate-spin">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none" class="opacity-30"/>
            <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="2" fill="none"/>
          </svg>
        </div>

        <h1 class="text-2xl font-bold tracking-wide">お問い合わせ（準備中）</h1>
        <p class="mt-3 text-sm leading-relaxed text-white/80">
          {{ message }}
        </p>

        <div class="mt-6 space-y-2 text-sm">
          <p class="text-white/70">
            お急ぎの方は
            <a :href="`mailto:${props.supportEmail}`" class="underline underline-offset-2 hover:opacity-80">
              {{ props.supportEmail }}
            </a>
            へメールにてご連絡ください。
          </p>
          <p>
            <Link href="/dashboard" class="underline underline-offset-2 hover:opacity-80">← ダッシュボードへ戻る</Link>
          </p>
        </div>

        <div class="mt-8 opacity-70 text-xs">
          近日公開予定：お問い合わせフォーム / 自動返信 / 受付番号発行
        </div>
      </div>
    </div>
  </AppLayout>
</template>
