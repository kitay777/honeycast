<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Link } from "@inertiajs/vue3";
import { ref } from "vue";

const showProhibited = ref(false);
</script>

<template>
    <AppLayout>
        <div
            class="pt-6 pb-28 px-6 text-white/90 text-lg bg-[url('/assets/imgs/back.png')] bg-no-repeat bg-center bg-[length:100%_100%]"
        >
            <h1 class="text-left text-3xl tracking-[0.5em] mb-10">
                ～ system ～
            </h1>

            <!-- チョ個っと -->
            <section class="mb-12">
                <h2 class="text-left text-2xl font-bold mb-3">『チョ個っと』（個別1：1）</h2>
                <p class="text-left mb-4">
                    お客様1名に対して、castが1名の場合
                </p>
                <div class="text-left space-y-3">
                    <p>cast 1名　1set　60分　<span class="font-bold text-2xl">¥20,000</span></p>
                    <p>2時間の場合 <span class="font-bold">¥40,000</span></p>
                    <p>3時間の場合 <span class="font-bold">¥60,000</span> になります。</p>
                    <p>基本サービスは全身リップ、フェラ、手コキ、素股等になります。</p>
                    <p>事前にシャワーを浴びて頂きます。</p>
                    <p class="text-sm opacity-70">※ホテル、自宅以外の場所での基本サービスは行えません。</p>
                    <p>指名料　castにより　<span class="font-bold text-2xl">¥2,000〜</span></p>
                    <p class="text-sm opacity-70">※指名料は1度きりで毎セットかかる訳ではございません。</p>
                    <p>派遣エリア：<span class="font-bold">新宿歌舞伎町（交通無料）</span></p>
                </div>
            </section>

            <!-- 団体チョコ -->
            <section class="mb-12">
                <h2 class="text-left text-2xl font-bold mb-3">『団体チョコ』（団体利用）</h2>
                <p class="text-left mb-4">
                    お客様複数名に対して、castが最低2名以上からご利用が可能になります。
                </p>
                <div class="text-left space-y-3">
                    <p>1set　60分　<span class="font-bold text-2xl">¥20,000</span></p>
                    <p>2時間の場合 <span class="font-bold">¥40,000</span></p>
                    <p>3時間の場合 <span class="font-bold">¥60,000</span> になります。</p>
                    <p>基本サービスは全身リップ、フェラ、手コキ、素股等になります。</p>
                    <p>事前にシャワーを浴びて頂きます。</p>
                    <p class="text-sm opacity-70">※ホテル、自宅以外の場所での基本サービスは行えません。</p>
                    <p>指名料　castにより　<span class="font-bold text-2xl">¥2,000〜</span></p>
                    <p class="text-sm opacity-70">※指名料は1度きりで毎セットかかる訳ではございません。</p>
                </div>
            </section>

            <!-- 延長料金 -->
            <section class="mb-12">
                <h2 class="text-left text-2xl font-bold mb-3">延長料金</h2>
                <div class="text-left space-y-3">
                    <p>cast 1名　1set　60分　<span class="font-bold text-2xl">¥22,000</span></p>
                    <p class="text-sm opacity-70">
                        ※castのシフト上の都合や、予約状況により延長ができない場合がございます。
                    </p>
                    <p>派遣エリア：<span class="font-bold">新宿歌舞伎町（交通無料）</span></p>
                    <p class="text-sm opacity-70">
                        オプションが可能となっていても、女の子の体調次第では対応できない場合がございます。
                    </p>
                </div>
            </section>

            <!-- 補足 -->
            <div class="text-left mb-10 space-y-4">
                <p>クレジットカード決済でも領収書発行可能となります。</p>

                <h3 class="font-bold text-xl mt-6 mb-2">基本内容について</h3>
                <p>・1セット60分制（自動延長）となりますので、時間の管理はお客様でお願い致します。CASTに終了時はお伝えください。</p>
                <p>・交通費は料金に含まれています。</p>
                <p>・どんな場所でもCASTを派遣可能です（ホテル、ご自宅以外だとできるゲームに限りがあります）。全てのサービスをお楽しみ頂きたいお客様は、ホテル・ご自宅にお呼び下さい。</p>

                <h3 class="font-bold text-xl mt-6 mb-2">派遣について</h3>
                <p>・CASTは1名から派遣可能です（お客様が複数人の場合で、CASTを1名呼ぶ事も可能です）。</p>
                <p>・お客様1名の場合のみ、CAST 1名との「チョ個っと」も可能です。</p>
                <p>・「個別1：1」でCASTが現地に着いた時にお客様が複数人いた場合は、ゲームの利用禁止または即派遣中止となります。その場合は1セット目の料金はいただきますのでお気をつけください。この場合、「チョ個っと」と「団体チョコ」の差額をお支払い頂ければ、「団体チョコ」に移行が可能です。</p>
                <p>・ゲームはCASTによって出来る・出来ないがありますため、CASTのプロフィールにてご確認くださいませ。</p>
                <p>・王様ゲームを選んで頂いた場合、それ以降どのゲームを行っても追加料金はかかりません（王様ゲームは全ゲームやり放題プランになります）。</p>
                <p>・お客様が「紳士であること」を前提に派遣させていただきます。CASTは放っておいても最高のパフォーマンスをいたしますのでどうか嫌がることはご遠慮ください。至らない点があれば最後のアンケートにて運営にご連絡ください。</p>
                <p>・万が一、CASTからの報告でお客様の素行や暴力、暴言などが発覚した際は、今後サービスの利用禁止とさせていただきます。</p>
                <p>・本番行為の強要も無断撮影等は禁止となります。発覚の際は即時に派遣中止となります。その際も料金は発生し返金はできません。今後サービスの利用禁止とさせていただきます。</p>
                <p>・お電話などによる応答・対応は一切いたしておりませんのでご了承下さい。</p>
            </div>

            <!-- 禁止事項ボタン（※構造そのまま） -->
            <div class="flex flex-col items-center gap-3 mt-12">
                <button
                    @click="showProhibited = !showProhibited"
                    class="px-14 py-4 rounded-full text-xl bg-gradient-to-r from-[#c45b5b] to-[#f0b1b1] text-black font-bold tracking-[0.5em] shadow text-left"
                >
                    禁止事項
                </button>

                <transition name="fade">
                    <div
                        v-if="showProhibited"
                        class="bg-black/70 text-white text-sm mt-6 p-6 rounded-2xl max-w-3xl leading-relaxed space-y-3"
                    >
                        <h3 class="text-left text-2xl font-bold mb-4">禁止事項</h3>
                        <ul class="list-disc list-inside space-y-2 text-left">
                            <li>盗撮、盗聴行為及びそれに準ずる疑いのある行為は固く禁じます。</li>
                            <li>同業者様や同業関係者様のご利用は固く禁じます。</li>
                            <li>本番行為等、サービス内容にない性交類似行為の強要を一切禁じます。</li>
                            <li>泥酔状態・薬物使用等にて正常な判断ができない方のご利用は固く禁じます。</li>
                            <li>女性に精神的苦痛を与える行為や言動等は固く禁じます。</li>
                            <li>無断で複数人が同室に居る、危険な小道具の使用なども固く禁じます。</li>
                            <li>同業者等による営業・スカウト行為は禁止です。</li>
                            <li>キャストとの個人的連絡先交換、または個人的な接触の勧誘行為を禁じます。</li>
                            <li>性病・感染症等の恐れがある方のご利用は固く禁じます。</li>
                            <li>お問い合わせ対応等で素行に問題があると判断した方のご利用は固く禁じます。</li>
                            <li>ご予約時間は目安であり、実際の案内が前後する場合があります。</li>
                            <li>お客様都合でのキャンセルは状況に応じて料金が発生します。</li>
                            <li>お客様持参の小道具等の持ち込みは衛生上禁止です。</li>
                            <li>不衛生または不適切な環境でのサービスはお断りします。</li>
                            <li>ホテル・ご自宅以外では一部サービスが行えません。</li>
                            <li>公序良俗または法令に反する行為は禁止です。</li>
                            <li>犯罪行為やそれを助長する行為は禁止です。</li>
                            <li>児童ポルノ・わいせつ物関連法令に違反する行為は禁止です。</li>
                            <li>サーバー負荷を与える行為、運営妨害行為は禁止です。</li>
                            <li>虚偽情報の登録、営業・勧誘目的での利用は禁止です。</li>
                            <li>不倫・援助交際・売春・買春などを目的とした利用は禁止です。</li>
                            <li>チェンジ・キャンセルは女性と合流後は受付できない場合があります。</li>
                            <li>外国のお客様は必ず予約時に申請が必要です。</li>
                            <li>爪が長い、清潔でない場合はお断りする場合があります。</li>
                            <li>飲食の強要はご遠慮ください。</li>
                            <li>差別・誹謗中傷・嫌がらせ・個人情報の公開行為は禁止です。</li>
                            <li>マルチ商法・ねずみ講の勧誘目的での利用は禁止です。</li>
                        </ul>

                        <p class="mt-6 text-sm text-left opacity-80">
                            ご予約頂いた時点で上記規約に同意したものとします。<br />
                            規約違反・不適切行為が発覚した場合、スタッフ判断により派遣を中止し、<br />
                            キャストを退出させます。その際の返金は一切応じられません。
                        </p>
                    </div>
                </transition>
            </div>

            <!-- 予約/ゲーム ボタン（位置・見た目はそのまま） -->
            <div class="flex flex-col items-center gap-3 mt-12">
                <Link
                    :href="route('call.create')"
                    class="px-14 py-4 rounded-full text-xl bg-gradient-to-r from-[#caa14b] to-[#f0e1b1] text-black font-bold tracking-[0.5em] shadow text-left"
                >
                    予約する
                </Link>

                <Link
                    :href="typeof route === 'function' ? route('games.index') : '/games'"
                    class="px-14 py-4 rounded-full text-xl bg-gradient-to-r from-[#6b7280] to-[#a1a1aa] text-white font-bold tracking-[0.5em] shadow text-left"
                >
                    ゲームを見る
                </Link>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active { transition: all 0.3s ease; }
.fade-enter-from,
.fade-leave-to { opacity: 0; transform: translateY(-10px); }
</style>
