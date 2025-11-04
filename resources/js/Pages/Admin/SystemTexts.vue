<script setup>
import { Head } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";

const texts = ref([]);
const loading = ref(false);
const saving = ref(false);
const error = ref("");

/** APIからロード */
async function loadTexts() {
    loading.value = true;
    try {
        const res = await fetch("/api/admin/system-texts");
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();
        texts.value = data.sort((a, b) => a.id - b.id); // ←ここで昇順ソート
    } catch (e) {
        console.error(e);
        error.value = "読み込みに失敗しました。";
    } finally {
        loading.value = false;
    }
}

/** 更新 */
async function save(key, content) {
    saving.value = true;
    try {
        const res = await fetch(`/api/admin/system-texts/${key}`, {
            method: "PUT",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content"),
            },
            body: JSON.stringify({ content }),
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
    } catch (e) {
        console.error(e);
        alert("保存に失敗しました。");
    } finally {
        saving.value = false;
    }
}

onMounted(() => loadTexts());
</script>

<template>
    <Head title="SYSTEM TEXTS 編集" />
    <AdminLayout active-key="system-texts">
        <template #header>
            <div
                class="px-5 py-3 bg-white border-b flex items-center justify-between"
            >
                <div class="text-xl font-semibold">SYSTEM TEXTS 編集</div>
                <div v-if="saving" class="text-sm text-gray-500 animate-pulse">
                    保存中...
                </div>
            </div>
        </template>

        <!-- 内容 -->
        <div class="p-6 bg-gray-50 min-h-[calc(100vh-64px)]">
            <div
                v-if="error"
                class="mb-4 px-4 py-3 bg-red-50 text-red-700 border border-red-200 rounded"
            >
                {{ error }}
            </div>

            <div
                v-if="loading"
                class="text-center text-gray-500 py-10 text-sm"
            >
                読み込み中...
            </div>

            <div v-else class="grid grid-cols-1 gap-6 max-w-4xl mx-auto">
                <div
                    v-for="t in texts"
                    :key="t.key"
                    class="bg-white rounded-2xl shadow p-5 border border-gray-100"
                >
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-lg font-semibold text-gray-800">
                            {{ t.key }}
                        </h2>
                        <button
                            type="button"
                            @click="save(t.key, t.content)"
                            class="text-sm px-3 py-1 rounded bg-black text-white hover:bg-gray-800"
                        >
                            保存
                        </button>
                    </div>
                    <textarea
                        v-model="t.content"
                        class="w-full h-56 p-3 border rounded-md font-mono text-sm leading-relaxed text-gray-800 focus:ring-2 focus:ring-yellow-400"
                    ></textarea>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
