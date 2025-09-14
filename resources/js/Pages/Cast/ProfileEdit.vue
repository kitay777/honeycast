<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
  cast: { type: Object, default: null },
  profile: { type: Object, default: null },
  pendingPermissions: { type: Array, default: () => [] },
});

// cast or profile のどちらでも
const p = computed(() => props.cast ?? props.profile ?? {});

// tags 配列保証
const initialTags = Array.isArray(p.value?.tags)
  ? p.value.tags
  : (p.value?.tags ? String(p.value.tags).split(/[\s,，、]+/).filter(Boolean) : []);

// フォーム初期化は p から
const form = useForm({
  id: p.value?.id ?? null,
  nickname: p.value?.nickname ?? "",
  rank: p.value?.rank ?? "",
  age: p.value?.age ?? "",
  height_cm: p.value?.height_cm ?? "",
  cup: p.value?.cup ?? "",
  style: p.value?.style ?? "",
  alcohol: p.value?.alcohol ?? "",
  mbti: p.value?.mbti ?? "",
  area: p.value?.area ?? "",
  tags: [...initialTags],
  freeword: p.value?.freeword ?? "",
  photo: null,
});

// プレビュー
const previewUrl = ref(p.value?.photo_path ? `/storage/${p.value.photo_path}` : null);
const onPhotoChange = (e) => {
  const f = e.target.files?.[0] ?? null;
  form.photo = f;
  if (f) previewUrl.value = URL.createObjectURL(f);
};

// route() フォールバック
const r = (...args) => (typeof route === "function" ? route(...args) : null);

// 申請承認/否認
const approve = (permId) => {
  const id = p.value?.id; if (!id) return;
  if (r) router.post(r("casts.unblur.approve", { castProfile: id, permission: permId }), { expires_at: null });
  else router.post(`/casts/${id}/unblur-requests/${permId}/approve`, { expires_at: null });
};
const deny = (permId) => {
  const id = p.value?.id; if (!id) return;
  if (r) router.post(r("casts.unblur.deny", { castProfile: id, permission: permId }));
  else router.post(`/casts/${id}/unblur-requests/${permId}/deny`);
};

// 保存
const submit = () => {
  if (r) form.post(r("cast.profile.update"), { forceFormData: true });
  else   form.post("/cast/profile/update",   { forceFormData: true });
};
</script>


<template>
    <AppLayout>
        <Head title="キャストプロフィール編集" />
        <div
            class="min-h-dvh w-screen flex items-center justify-center bg-black"
        >
            <div
                class="relative w-full h-dvh md:w-[390px] md:h-[844px] mx-auto bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%] overflow-y-auto"
            >
                <div class="px-6 pt-8 pb-24 text-white/90">
                    <h1 class="text-2xl font-semibold mb-6">
                        プロフィール編集
                    </h1>

                    <!-- 顔写真 -->
                    <div class="mb-6">
                        <div class="flex items-center gap-4">
                            <div
                                class="w-28 h-28 rounded-full overflow-hidden bg-white/10 shrink-0"
                            >
                                <img
                                    v-if="previewUrl"
                                    :src="previewUrl"
                                    class="w-full h-full object-cover"
                                />
                                <div
                                    v-else
                                    class="w-full h-full flex items-center justify-center text-sm text-white/60"
                                >
                                    No Photo
                                </div>
                            </div>
                            <label
                                class="inline-block px-4 py-2 rounded-md bg-yellow-200 text-black font-semibold cursor-pointer"
                            >
                                画像を選ぶ
                                <input
                                    type="file"
                                    class="hidden"
                                    accept="image/*"
                                    @change="onPhotoChange"
                                />
                            </label>
                        </div>
                        <p class="mt-2 text-xs text-white/60">
                            jpeg/png/gif/webp、4MBまで
                        </p>
                        <div
                            v-if="form.errors.photo"
                            class="text-xs text-red-300 mt-1"
                        >
                            {{ form.errors.photo }}
                        </div>
                    </div>

    <div v-if="(pendingPermissions?.length || 0) > 0" class="mb-6">
      <h3 class="font-bold mt-6 mb-2">未処理の閲覧申請</h3>
      <ul class="space-y-2">
        <li v-for="pmt in pendingPermissions" :key="pmt.id" class="p-3 rounded border">
          <div class="text-sm opacity-70">申請者: {{ pmt.viewer.name }} (ID: {{ pmt.viewer.id }})</div>
          <div class="text-sm">メッセージ: {{ pmt.message || "（なし）" }}</div>
          <div class="mt-2 space-x-2">
            <button @click="approve(pmt.id)" class="bg-green-600 text-white rounded px-3 py-1">承認</button>
            <button @click="deny(pmt.id)" class="bg-gray-400 text-white rounded px-3 py-1">否認</button>
          </div>
        </li>
      </ul>
    </div>

                    <Link
                        :href="`/casts/${form.id}/schedule`"
                        class="inline-block text-sm underline text-yellow-200"
                    >
                        ● スケジュール編集へ
                    </Link>
                    <Link
                        href="/tweets"
                        class="inline-block text-sm underline text-yellow-200 ml-4"
                    >
                        ● ツイート
                    </Link>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="inline-block text-sm underline text-yellow-200 ml-4"
                    >
                        ● ログアウト
                    </Link>
                    <form @submit.prevent="submit" class="space-y-5">
                        <div>
                            <label class="block mb-1 text-sm"
                                >ニックネーム</label
                            >
                            <input
                                v-model="form.nickname"
                                type="text"
                                class="w-full h-11 rounded-md px-3 text-black"
                            />
                            <p
                                v-if="form.errors.nickname"
                                class="text-xs text-red-300 mt-1"
                            >
                                {{ form.errors.nickname }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block mb-1 text-sm">ランク</label>
                                <input
                                    v-model.number="form.rank"
                                    type="number"
                                    min="0"
                                    max="99"
                                    class="w-full h-11 rounded-md px-3 text-black"
                                />
                            </div>
                            <div>
                                <label class="block mb-1 text-sm">年齢</label>
                                <input
                                    v-model.number="form.age"
                                    type="number"
                                    min="18"
                                    max="99"
                                    class="w-full h-11 rounded-md px-3 text-black"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block mb-1 text-sm"
                                    >身長(cm)</label
                                >
                                <input
                                    v-model.number="form.height_cm"
                                    type="number"
                                    min="120"
                                    max="220"
                                    class="w-full h-11 rounded-md px-3 text-black"
                                />
                            </div>
                            <div>
                                <label class="block mb-1 text-sm">カップ</label>
                                <input
                                    v-model="form.cup"
                                    type="text"
                                    placeholder="A〜H等"
                                    class="w-full h-11 rounded-md px-3 text-black"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block mb-1 text-sm">エリア</label>
                            <select
                                v-model="form.area"
                                class="w-full h-11 rounded-md px-3 text-black"
                            >
                                <option value="">選択してください</option>
                                <option>北海道・東北</option>
                                <option>関東</option>
                                <option>中部</option>
                                <option>近畿</option>
                                <option>中国・四国</option>
                                <option>九州・沖縄</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block mb-1 text-sm"
                                    >スタイル</label
                                >
                                <select
                                    v-model="form.style"
                                    class="w-full h-11 rounded-md px-3 text-black"
                                >
                                    <option value="">未選択</option>
                                    <option>スレンダー</option>
                                    <option>細身</option>
                                    <option>グラマー</option>
                                    <option>その他</option>
                                </select>
                            </div>
                            <div>
                                <label class="block mb-1 text-sm">お酒</label>
                                <select
                                    v-model="form.alcohol"
                                    class="w-full h-11 rounded-md px-3 text-black"
                                >
                                    <option value="">未選択</option>
                                    <option>飲む</option>
                                    <option>少し</option>
                                    <option>飲まない</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block mb-1 text-sm">MBTI</label>
                            <input
                                v-model="form.mbti"
                                maxlength="4"
                                placeholder="ENFPなど"
                                class="w-full h-11 rounded-md px-3 text-black uppercase"
                            />
                        </div>

                        <div>
                            <label class="block mb-2 text-sm">タグ</label>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="t in candidateTags"
                                    :key="t"
                                    type="button"
                                    @click="toggleTag(t)"
                                    class="px-3 py-1 rounded-full text-sm"
                                    :class="
                                        form.tags.includes(t)
                                            ? 'bg-yellow-200 text-black'
                                            : 'bg:white/20 bg-white/20'
                                    "
                                >
                                    {{ t }}
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block mb-1 text-sm">自己紹介</label>
                            <textarea
                                v-model="form.freeword"
                                rows="4"
                                class="w-full rounded-md px-3 py-2 text-black"
                            ></textarea>
                        </div>

                        <div class="mt-4">
                            <button
                                :disabled="form.processing"
                                class="w-full h-12 rounded-md font-bold tracking-[0.5em] bg-[#7a560f] text-white border border-[#c79a2b] shadow hover:brightness-110"
                            >
                                更　新
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
