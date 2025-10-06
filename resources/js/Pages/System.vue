<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'
</script>

<template>
  <AppLayout>
    <div class="pt-6 pb-28 px-6 text-white/90 text-lg
                bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%]">

      <h1 class="text-center text-3xl tracking-[0.5em] mb-10">～ system ～</h1>

      <!-- 1:1 -->
      <section class="mb-12">
        <h2 class="text-center text-2xl font-bold mb-3">1 : 1</h2>
        <p class="text-center mb-4">
          お客様1名に対して、castが1名の場合
        </p>
        <div class="text-center space-y-3">
          <p>cast 1名　1set　<span class="font-bold text-2xl">¥20,000</span></p>
          <p>指名料　castにより　<span class="font-bold text-2xl">¥2,000〜</span></p>
          <p class="text-sm opacity-70">※指名料は1度きりで毎セットかかる訳ではございません。</p>
        </div>
      </section>

      <!-- 団体利用 -->
      <section class="mb-12">
        <h2 class="text-center text-2xl font-bold mb-3">団体利用</h2>
        <p class="text-center mb-4">
          お客様複数名に対して、castが最低2名以上からご利用が可能になります。
        </p>
        <div class="text-center space-y-3">
          <p>cast 1名　1set　<span class="font-bold text-2xl">¥20,000</span></p>
          <p>指名料　castにより　<span class="font-bold text-2xl">¥2,000〜</span></p>
          <p class="text-sm opacity-70">※指名料は1度きりで毎セットかかる訳ではございません。</p>
        </div>
      </section>

      <!-- 延長料金 -->
      <section class="mb-12">
        <h2 class="text-center text-2xl font-bold mb-3">延長料金</h2>
        <div class="text-center space-y-3">
          <p>cast 1名　1set　<span class="font-bold text-2xl">¥22,000</span></p>
          <p class="text-sm opacity-70">
            ※castのシフト上の都合や、予約状況により延長ができない場合がございます。
          </p>
        </div>
      </section>

      <!-- 補足 -->
      <div class="text-center mb-10">
        クレジットカード決済でも領収書発行可能となります。
      </div>

<!-- ボタン -->
      <div class="flex flex-col items-center gap-3">
        <!-- 予約する -->
        <Link :href="route('call.create')"
              class="px-14 py-4 rounded-full text-xl bg-gradient-to-r from-[#caa14b] to-[#f0e1b1]
                     text-black font-bold tracking-[0.5em] shadow text-center">
          予約する
        </Link>

        <!-- ゲームを見る -->
        <Link :href="(typeof route==='function') ? route('games.index') : '/games'"
              class="px-14 py-4 rounded-full text-xl bg-gradient-to-r from-[#6b7280] to-[#a1a1aa]
                     text-white font-bold tracking-[0.5em] shadow text-center">
          ゲームを見る
        </Link>
      </div>
    </div>
  </AppLayout>
</template>
