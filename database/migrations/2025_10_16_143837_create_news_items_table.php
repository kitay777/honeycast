<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('news_items', function (Blueprint $t) {
      $t->id();
      $t->string('title', 200);
      $t->text('body')->nullable();
      $t->string('url', 2000)->nullable();     // “続きを読む” などの外部/内部リンク
      $t->timestamp('published_at')->nullable(); // 公開日時（nullは即時扱い）
      $t->boolean('is_active')->default(true);
      $t->unsignedSmallInteger('priority')->default(100); // 小さいほど先
      $t->timestamps();
      $t->index(['is_active','published_at','priority']);
    });
  }
  public function down(): void {
    Schema::dropIfExists('news_items');
  }
};
