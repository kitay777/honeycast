<template>
  <div class="bg-[#2b241b]/60 text-yellow-100 rounded border border-[#d1b05a]/30 flex justify-between px-3 py-2">
    <div class="opacity-80">{{ label }}</div>
    <div class="font-medium">{{ value || '—' }}</div>
  </div>
</template>

<script setup>
defineProps({
  label: String,
  value: String,
})
</script>
