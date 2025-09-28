<!-- resources/js/Pages/Cast/Show.vue （完全版） -->
<script setup>
import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({
  cast: { type: Object, required: true },     // cast.photos を受け取る
  schedule: { type: Array, default: () => [] },
  unblur: { type: Object, default: () => ({ requested:false, status:null }) },
});

const gallery = computed(() => Array.isArray(props.cast.photos) ? props.cast.photos : []);
// 表示中の写真（プライマリ→先頭→単発photo_path）
const current = ref(
  gallery.value.find(p => p.is_primary) ??
  gallery.value[0] ??
  null
);

/** プロファイル基準のぼかし（後方互換） */
const shouldBlurProfile = computed(() => {
  const supplied = props.cast?.should_blur;
  if (supplied !== undefined && supplied !== null) return !!supplied;
  const def = props.cast?.is_blur_default;
  const hasAccess = !!props.cast?.viewer_has_unblur_access;
  const defaultFlag = (def === undefined || def === null) ? true : !!def;
  return defaultFlag && !hasAccess;
});

/** 表示中写真のぼかし（個別優先） */
const shouldBlur = computed(() => {
  if (current.value) {
    if (current.value.is_primary) return false;                   // ★ primary は常に非ブラー
    if (typeof current.value.should_blur === 'boolean') return current.value.should_blur;
    }
  return shouldBlurProfile.value;
});

/** プロファイル単位の申請 */
const hasUnblurRequest = computed(() => !!props.unblur?.requested);
const unblurStatus = computed(() => props.unblur?.status ?? null);
const requesting = ref(false);
const requestUnblurProfile = () => {
  if (requesting.value) return;
  requesting.value = true;
  router.post(`/casts/${props.cast.id}/unblur-requests`, {}, {
    onFinish: () => { requesting.value = false; }
  });
};

/** 写真ごとの申請 */
const requestingPhoto = ref({});
const requestUnblurPhoto = (photoId) => {
  if (requestingPhoto.value[photoId]) return;
  requestingPhoto.value = { ...requestingPhoto.value, [photoId]: true };
  router.post(`/photos/${photoId}/unblur-requests`, {}, {
    onFinish: () => { requestingPhoto.value = { ...requestingPhoto.value, [photoId]: false }; }
  });
};

const startingChat = ref(false);
const startChat = () => {
  if (startingChat.value) return;
  startingChat.value = true;
  router.post(route("casts.startChat", props.cast.id), {}, { onFinish: () => { startingChat.value = false; }});
};
</script>

<template>
  <AppLayout>
    <div class="pt-4 pb-28 px-4 text-white/90 bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%]">

      <!-- 顔写真 + 名前行 -->
      <section class="mx-auto max-w-[780px] bg-[#2b241b]/60 rounded-lg border border-[#d1b05a]/50 p-3">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="inline-block w-3 h-3 rounded-full bg-green-400"></span>
            <div class="text-xl font-semibold tracking-wide">
              {{ props.cast.nickname ?? "name" }}
            </div>
          </div>
          <img src="/assets/icons/like-badge.png" class="h-8" />
        </div>

        <!-- メイン写真 -->
        <div class="mt-2 relative aspect-[3/2] bg-white rounded overflow-hidden ring-1 ring-black/10">
          <img
            :src="current
                    ? current.url
                    : (props.cast.photo_path ? `/storage/${props.cast.photo_path}` : '/assets/imgs/placeholder.png')"
            class="w-full h-full object-cover transition will-change-transform"
            :class="shouldBlur ? 'blur-lg scale-105' : ''"
            draggable="false"
          />
          <div v-if="shouldBlur" class="absolute top-2 left-2 bg-black/45 text-white text-xs px-2 py-1 rounded">🔒 ぼかし中</div>
        </div>

        <!-- サブ写真（横スクロールのサムネ＋個別申請） -->
        <div v-if="gallery.length" class="mt-3 relative">
          <div class="flex gap-3 overflow-x-auto no-scrollbar -mx-2 px-2 py-1">
 <div
   v-for="p in gallery" :key="p.id"
   class="shrink-0 w-28 h-20 rounded overflow-hidden ring-1 ring-black/20 relative cursor-pointer"
   @click="current = p" role="button" tabindex="0"
 >
              <img :src="p.url" class="w-full h-full object-cover" :class="p.should_blur ? 'blur-md scale-105' : ''" />
              <!-- 個別申請ボタン（ぼかし中・未申請の時だけ） -->
               <div v-if="p.should_blur && !(p.unblur?.requested)"
                    class="absolute inset-0 flex items-center justify-center bg-black/35 z-10">
                <button
                  class="px-2 py-1 text-xs rounded bg-yellow-200 text-black disabled:opacity-60"
                  :disabled="requestingPhoto[p.id]"
                  @click.stop="requestUnblurPhoto(p.id)"
                >
                  申請
                </button>
              </div>
              <div v-else-if="p.should_blur && p.unblur?.requested"
                   class="absolute bottom-1 right-1 text-[10px] bg-black/55 text-white px-1 rounded z-10">
                申請済
              </div>
   <div v-if="current && current.id===p.id"
        class="absolute inset-0 ring-2 ring-yellow-300 rounded pointer-events-none"></div>
 </div>
          </div>
        </div>

        <!-- 星とアクション -->
        <div class="mt-2 flex items-center justify-between">
          <div class="text-[#ffcc66]">★ ★ ★ ★ ☆</div>

          <div class="flex items-center gap-3">
            <!-- プロファイル単位のぼかし解除申請 -->
             <!--
            <button
              v-if="shouldBlurProfile && !hasUnblurRequest"
              @click="requestUnblurProfile"
              :disabled="requesting"
              class="px-4 py-2 rounded bg-[#ffe7b3] text-black shadow disabled:opacity-60 disabled:pointer-events-none"
            >
              ぼかしを外す申請
            </button>
            <span v-else-if="shouldBlurProfile && hasUnblurRequest"
              class="px-4 py-2 rounded bg-[#bfb6a3] text-black/90 shadow text-sm">
              申請済み<span v-if="unblurStatus">（{{ unblurStatus }}）</span>
            </span>
            -->
            <button @click="startChat" :disabled="startingChat"
              class="px-4 py-2 rounded bg-[#e7d7a0] text-black shadow disabled:opacity-60">
              {{ startingChat ? "開始中..." : "メッセージを送る" }}
            </button>
            <button class="px-4 py-2 rounded bg-[#a99a86] text-black shadow">指名する</button>
          </div>
        </div>
      </section>

      <!-- スケジュール -->
      <section class="mx-auto max-w-[780px] mt-6">
        <div class="text-center text-lg bg-[#6b4b17] border border-[#d1b05a] py-1 rounded">スケジュール</div>
        <div class="mt-3 grid grid-cols-7 gap-1 text-center text-sm">
          <div v-for="d in props.schedule" :key="d.date" class="bg-[#2b241b]/60 rounded border border-[#d1b05a]/30 p-2">
            <div class="text-xs opacity-80">{{ d.date }}</div>
            <div class="opacity-80">{{ d.weekday }}</div>
            <div class="mt-2 text-yellow-200 text-xs" v-if="d.slots?.length">
              <div v-for="(s, i) in d.slots" :key="i">{{ s.start }} - {{ s.end }}</div>
            </div>
            <div class="mt-4 text-xs opacity-50" v-else>未設定</div>
          </div>
        </div>
      </section>

      <!-- プロフィール表 -->
      <section class="mx-auto max-w-[780px] mt-8">
        <div class="grid grid-cols-2 gap-2">
          <InfoRow label="エリア" :value="props.cast.area" />
          <InfoRow label="身長" :value="props.cast.height_cm ? props.cast.height_cm + ' cm' : ''" />
          <InfoRow label="年齢" :value="props.cast.age ? props.cast.age + ' 歳' : ''" />
          <InfoRow label="カップ" :value="props.cast.cup" />
          <InfoRow label="スタイル" :value="props.cast.style" />
          <InfoRow label="お酒" :value="props.cast.alcohol" />
          <InfoRow label="MBTI" :value="props.cast.mbti" />
        </div>

        <div class="mt-6">
          <div class="text-sm opacity-80 mb-1">自己紹介</div>
          <div class="rounded bg-[#2b241b]/60 border border-[#d1b05a]/30 p-3 min-h-[120px]">
            {{ props.cast.freeword || "—" }}
          </div>
        </div>

        <div class="mt-6">
          <div class="text-sm opacity-80 mb-2">タグ</div>
          <div class="flex flex-wrap gap-2">
            <span v-for="t in props.cast.tags || []" :key="t"
                  class="px-3 py-1 rounded-full bg-[#ffe09a] text-black text-xs shadow">{{ t }}</span>
            <span v-if="!(props.cast.tags && props.cast.tags.length)" class="opacity-60 text-sm">—</span>
          </div>
        </div>
      </section>
    </div>
  </AppLayout>
</template>

<script>
export default {
  components: {
    InfoRow: {
      props: { label: String, value: String },
      template: `
        <div class="bg-[#2b241b]/60 rounded border border-[#d1b05a]/30 flex justify-between px-3 py-2">
          <div class="opacity-80">{{ label }}</div>
          <div class="font-medium">{{ value || '—' }}</div>
        </div>
      `,
    },
  },
};
</script>
