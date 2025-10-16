<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('text_banners', function (Blueprint $t) {
      $t->id();
      $t->string('message');          // 表示するテキスト
      $t->string('url')->nullable();  // クリック先
      $t->unsignedSmallInteger('speed')->default(60); // px/秒 相当
      $t->boolean('is_active')->default(true);
      $t->timestamp('starts_at')->nullable();
      $t->timestamp('ends_at')->nullable();
      $t->unsignedSmallInteger('priority')->default(100);
      $t->string('bg_color')->default('#111111');
      $t->string('text_color')->default('#FFE08A');
      $t->timestamps();
      $t->index(['is_active','starts_at','ends_at','priority']);
    });
  }
  public function down(): void { Schema::dropIfExists('text_banners'); }
};