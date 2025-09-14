<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cast_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            // 検索で使いそうな基本項目
            $table->string('nickname')->nullable();
            $table->unsignedTinyInteger('rank')->nullable();      // 1〜99 など
            $table->unsignedTinyInteger('age')->nullable();       // 年齢
            $table->unsignedSmallInteger('height_cm')->nullable();// 身長
            $table->string('cup', 5)->nullable();                 // A〜H 等（自由入力にしておく）
            $table->string('style')->nullable();                  // スレンダー/グラマー 等
            $table->string('alcohol')->nullable();                // 飲む/少し/飲まない 等
            $table->string('mbti', 4)->nullable();                // ENFP 等
            $table->string('area')->nullable();                   // 地域

            // タグは複数 → JSON 配列で保持
            $table->json('tags')->nullable();

            // 自己紹介など
            $table->text('freeword')->nullable();

            // 顔写真（1枚想定のカラム。複数は別テーブルで拡張可）
            $table->string('photo_path')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cast_profiles');
    }
};
