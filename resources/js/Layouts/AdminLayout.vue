<script setup>
import AdminSidebar from '@/Components/Admin/AdminSidebar.vue' // ※ @ が未設定なら相対パスに
const props = defineProps({
  activeKey: { type: String, default: '' },
  menus: { type: Array, default: undefined },
})
</script>

<template>
  <div class="h-screen overflow-hidden bg-gray-50">
    <div class="grid grid-cols-12 h-full">
      <AdminSidebar :active-key="activeKey" :menus="menus" />
      <!-- 右側。高さ依存の処理があるため id=right-pane をここで提供 -->
      <section id="right-pane"
               class="col-span-12 md:col-span-10 md:ml-56 flex flex-col relative z-10 h-full">
        <slot name="header" />
        <slot />
      </section>
    </div>
  </div>
</template>
