<script setup>
import AdminSidebar from '@/Components/Admin/AdminSidebar.vue' // ※ @ が未設定なら相対パスに
const props = defineProps({
  activeKey: { type: String, default: '' },
  menus: { type: Array, default: undefined },
})
</script>

<template>
  <!-- 全体の縦は“伸びる”、横だけ隠す（スマホSafari対応で 100dvh） -->
  <div class="min-h-[100dvh] overflow-x-hidden bg-gray-50">
    <div class="grid grid-cols-12 min-h-[100dvh]">
      <!-- 左サイドバー（必要なら sticky で縦スクロール可） -->
      <AdminSidebar :active-key="activeKey" :menus="menus" />

      <!-- 右側：縦は伸びる + 上：ヘッダー / 下：スクロール領域 -->
      <section
        class="col-span-12 md:col-span-10 md:ml-56 relative z-10 min-h-[100dvh] flex flex-col"
      >
        <!-- 非スクロール領域（ページごとのヘッダ） -->
        <slot name="header" />

        <!-- スクロール領域（ここを画面ごとの “right-pane” として使う） -->
        <div id="right-pane" class="flex-1 overflow-auto">
          <slot />
        </div>
      </section>
    </div>
  </div>
</template>
