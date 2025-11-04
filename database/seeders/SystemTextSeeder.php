<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemText;

class SystemTextSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            '個別チョコ' => '<h2>『個別チョコ』（個別1：1）</h2><p>お客様1名に対してcastが1名の場合…</p>',
            '団体チョコ' => '<h2>『団体チョコ』（団体利用）</h2><p>お客様複数名に対してcastが最低2名以上からご利用可能…</p>',
            'チョ個っと' => '<h2>『チョ個っと』</h2><p>日中限定（10:00～20:00）プラン…</p>',
            'チョコデリ' => '<h2>『チョコデリ』（通常デリヘル）</h2><p>45分 ¥15,000〜の通常サービス…</p>',
        ];

        foreach ($defaults as $key => $content) {
            SystemText::updateOrCreate(['key' => $key], ['content' => $content]);
        }
    }
}
