<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, router } from '@inertiajs/vue3'

const form = useForm({
  freeword: '',
  rank_min: '', rank_max: '',
  age_min: '',  age_max:  '',
  area: '',
  height_min: '', height_max: '',
  cup_min: '',   cup_max:  '',
  style: '',
  alcohol: '',
  mbti: '',
  tags: [],
})

const areas  = ['北海道・東北','関東','中部','近畿','中国・四国','九州・沖縄']
const cups   = ['A','B','C','D','E','F','G','H']
const styles = ['スレンダー','細身','グラマー','その他']
const alcohols = ['飲む','少し','飲まない']

const candidateTags = [
  'ギャル','清楚','アイドル','オタク','可愛い','キレイ',
  '高身長','低身長','スレンダー','細身','グラマー','ロングヘア',
  'ショートヘア','金髪','茶髪','黒髪','明るい','ワイワイ'
]

const toggleTag = (t) => {
  const i = form.tags.indexOf(t)
  i >= 0 ? form.tags.splice(i,1) : form.tags.push(t)
}

const submit = () => {
  // /dashboard に GET でクエリを付けて遷移
  router.get('/dashboard', form.data(), { preserveScroll: true, preserveState: true })
}
</script>

<template>
  <AppLayout>
    <!-- 背景や上下余白はレイアウト側に合わせて調整 -->
    <div class="px-6 pt-6 pb-28 text-black/90">
      <h1 class="text-center text-2xl tracking-widest mb-4">探 す</h1>
      <div class="h-[2px] w-full bg-gradient-to-r from-[#c8a64a] via-[#e6d08a] to-[#c8a64a] mb-6"></div>

      <!-- 入力枠の見た目（白枠っぽく）を共通化 -->
      <style>
        .field {
          @apply w-full h-11 rounded bg-white text-black px-3 outline-none;
        }
        .outline-box {
          /* 白の細枠っぽい雰囲気 */
          box-shadow: inset 0 0 0 2px rgba(255,255,255,.85), 0 0 0 1px rgba(0,0,0,.2);
          border-radius: .375rem;
        }
      </style>

      <form @submit.prevent="submit" class="space-y-4">
        <!-- 1行：フリーワード -->
        <div class="flex gap-4 items-center">
          <div class="w-20 shrink-0 text-sm text-white/90">フリーワード</div>
          <div class="flex-1 outline-box">
            <input v-model="form.freeword" type="text" class="field" />
          </div>
        </div>

        <!-- ランク / 年齢 -->
        <div class="flex gap-4 items-center">
          <div class="w-20 shrink-0 text-sm text-white/90">ランク</div>
          <div class="flex-1 flex items-center gap-2">
            <div class="flex-1 outline-box">
              <input v-model="form.rank_min" type="number" class="field" />
            </div>
            <span class="opacity-80 text-white/90">〜</span>
            <div class="flex-1 outline-box">
              <input v-model="form.rank_max" type="number" class="field" />
            </div>
          </div>
        </div>

        <div class="flex gap-4 items-center">
          <div class="w-20 shrink-0 text-sm text-white/90">年齢</div>
          <div class="flex-1 flex items-center gap-2">
            <div class="flex-1 outline-box">
              <input v-model="form.age_min" type="number" class="field" />
            </div>
            <span class="opacity-80 text-white/90">〜</span>
            <div class="flex-1 outline-box">
              <input v-model="form.age_max" type="number" class="field" />
            </div>
          </div>
        </div>

        <!-- エリア -->
        <div class="flex gap-4 items-center">
          <div class="w-20 shrink-0 text-sm text-white/90">エリア</div>
          <div class="flex-1 outline-box">
            <select v-model="form.area" class="field">
              <option value="">選択してください</option>
              <option v-for="a in areas" :key="a" :value="a">{{ a }}</option>
            </select>
          </div>
        </div>

        <!-- 身長 -->
        <div class="flex gap-4 items-center">
          <div class="w-20 shrink-0 text-sm text-white/90">身長</div>
          <div class="flex-1 flex items-center gap-2">
            <div class="flex-1 outline-box">
              <input v-model="form.height_min" type="number" class="field" />
            </div>
            <span class="opacity-80 text-white/90">〜</span>
            <div class="flex-1 outline-box">
              <input v-model="form.height_max" type="number" class="field" />
            </div>
          </div>
        </div>

        <!-- カップ -->
        <div class="flex gap-4 items-center">
          <div class="w-20 shrink-0 text-sm text-white/90">カップ</div>
          <div class="flex-1 flex items-center gap-2">
            <div class="flex-1 outline-box">
              <select v-model="form.cup_min" class="field">
                <option value="">—</option>
                <option v-for="c in cups" :key="'min'+c" :value="c">{{ c }}</option>
              </select>
            </div>
            <span class="opacity-80 text-white/90">〜</span>
            <div class="flex-1 outline-box">
              <select v-model="form.cup_max" class="field">
                <option value="">—</option>
                <option v-for="c in cups" :key="'max'+c" :value="c">{{ c }}</option>
              </select>
            </div>
          </div>
        </div>

        <!-- スタイル -->
        <div class="flex gap-4 items-center">
          <div class="w-20 shrink-0 text-sm text-white/90">スタイル</div>
          <div class="flex-1 outline-box">
            <select v-model="form.style" class="field">
              <option value="">未選択</option>
              <option v-for="s in styles" :key="s" :value="s">{{ s }}</option>
            </select>
          </div>
        </div>

        <!-- お酒 -->
        <div class="flex gap-4 items-center">
          <div class="w-20 shrink-0 text-sm text-white/90">お酒</div>
          <div class="flex-1 outline-box">
            <select v-model="form.alcohol" class="field">
              <option value="">未選択</option>
              <option v-for="a in alcohols" :key="a" :value="a">{{ a }}</option>
            </select>
          </div>
        </div>

        <!-- MBTI -->
        <div class="flex gap-4 items-center">
          <div class="w-20 shrink-0 text-sm text-white/90">MBTI</div>
          <div class="flex-1 outline-box">
            <input v-model="form.mbti" maxlength="4" class="field uppercase tracking-wide" placeholder="ENFP など" />
          </div>
        </div>

        <!-- タグ -->
        <div class="mt-4">
          <div class="text-lg font-semibold mb-2 text-white/90">タグ</div>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="t in candidateTags"
              :key="t"
              type="button"
              @click="toggleTag(t)"
              class="px-3 py-1 rounded-full text-sm shadow"
              :class="form.tags.includes(t)
                ? 'bg-[#ffe09a] text-black'
                : 'bg-white/20 text-white'"
            >
              {{ t }}
            </button>
          </div>
        </div>

        <!-- 検索ボタン（矢印つき） -->
        <div class="mt-6 flex justify-center">
          <button
            type="submit"
            class="relative w-72 h-14 rounded-full font-bold text-2xl tracking-widest
                   bg-gradient-to-r from-[#caa14b] to-[#f0e1b1] text-white
                   shadow-[0_6px_0_rgba(0,0,0,.25)]"
          >
            検 索
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
