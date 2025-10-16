<script setup>
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
  items:   Object,
  filters: Object,
  areas:   Array,
})

const q    = ref(props.filters?.q ?? '')
const area = ref(props.filters?.area ?? '')

const search = () => {
  router.get(route('hotels.index'), { q: q.value, area: area.value }, {
    preserveScroll: true,
    replace: true,
  })
}
</script>


<template>
  <AppLayout>
    <div class="px-4 py-6 text-white/90 bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%]">
      <div class="max-w-6xl mx-auto">
        <div class="flex items-center justify-between mb-4">
          <h1 class="text-2xl font-bold">ホテル一覧</h1>
          <div class="flex gap-2">
            <input v-model="q" @keyup.enter="search" placeholder="名称/住所"
                   class="px-3 py-2 rounded bg-white/10"/>
            <select v-model="area" @change="search" class="px-3 py-2 rounded bg-white/10">
              <option value="">すべてのエリア</option>
              <option v-for="a in props.areas" :key="a" :value="a">{{ a }}</option>
            </select>
            <button @click="search" class="px-3 py-2 rounded bg-emerald-500">検索</button>
          </div>
        </div>

        <div v-if="!props.items.data.length" class="text-white/70">該当するホテルはありません。</div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <Link v-for="h in props.items.data" :key="h.id"
                :href="route('hotels.show', h.id)"
                class="block rounded-lg bg-white/10 border border-white/20 overflow-hidden hover:brightness-110">
            <img v-if="h.image" :src="h.image" class="h-36 w-full object-cover"/>
            <div class="p-3">
              <div class="font-semibold">{{ h.name }}</div>
              <div class="text-xs text-white/70">{{ h.area }}</div>
              <div class="text-xs mt-1 line-clamp-2">{{ h.address }}</div>
            </div>
          </Link>
        </div>

        <!-- 簡易ページネーション -->
        <div class="mt-6 flex justify-center gap-2" v-if="props.items.links?.length">
          <Link v-for="(l,i) in props.items.links" :key="i" :href="l.url || '#'"
                class="px-3 py-1 rounded bg-white/10 border border-white/20"
                :class="{ 'opacity-50 pointer-events-none': !l.url, '!bg-emerald-600': l.active }"
                v-html="l.label"/>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
