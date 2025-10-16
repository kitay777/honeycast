<script setup>
import { router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  activeKey: { type: String, default: '' },
  menus: {
    type: Array,
    default: () => ([
      { key: 'users', label: 'キャスト管理', to: '/admin/users' },
      { key: 'casts', label: 'ユーザー管理', to: '/admin/casts' },
      { key: 'requests',   label: 'リクエスト',   to: '/admin/requests' },
      { key: 'schedules', label: 'スケジュール', to: '/admin/schedules' },
      { key: 'shops', label: 'ショップ管理', to: '/admin/shops' },
      { key: 'tags', label: 'タグ管理', to: '/admin/tags' },
      { key: 'ng', label: '禁止ワード', to: '/admin/ng-words' },
      { key: 'games', label: '動画（ゲーム）管理', to: '/admin/games' },
      { key: 'banners', label: 'バナー管理', to: '/admin/banners' },
      { key: 'faqs', label: 'FAQ管理', to: '/admin/faqs' },
      { key: 'inquiries', label: 'お問い合わせ', to: '/admin/inquiries' },
      { key: 'settings', label: '各種設定', to: '/admin/settings' },
      { key: 'ng', label: '禁止ワード', to: '/admin/ng-words' },

      { key: 'ng', label: 'テロップ', to: '/admin/text-banners' },
      { key: 'ng', label: '広告', to: '/admin/ad-banners' },


      { key: 'ng', label: '新着情報', to: '/admin/news' },
      { key: 'ng', label: 'イベント', to: '/admin/events' },

      { key: 'ng', label: 'ホテル', to: '/admin/hotels' },
    ]),
  },
})

const page = usePage()
const url = computed(() => page?.url || window.location.pathname)

function isActive(m) {
  if (props.activeKey && m.key === props.activeKey) return true
  try { return url.value.startsWith(m.to) } catch { return false }
}

function nav(to) {
  try {
    router.visit(to, {
      replace: false,
      preserveScroll: false,
      onError: () => window.location.assign(to),
      onCancel: () => window.location.assign(to),
    })
  } catch { window.location.assign(to) }
}
</script>

<template>
  <aside class="fixed md:static top-0 left-0 h-full md:h-auto w-56 md:w-auto border-r bg-white z-50">
    <nav class="px-2 py-4 space-y-1 select-none">
      <button v-for="m in menus" :key="m.key" type="button"
              @click="nav(m.to)"
              class="w-full text-left px-3 py-2 rounded-lg"
              :class="isActive(m) ? 'bg-black text-white' : 'hover:bg-gray-100'">
        {{ m.label }}
      </button>
      <!-- 追加の要素を入れたいとき用 -->
      <slot />
    </nav>
  </aside>
</template>
