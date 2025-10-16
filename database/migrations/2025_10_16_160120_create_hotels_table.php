<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('hotels', function (Blueprint $t) {
      $t->id();
      $t->string('name', 200);
      $t->string('area', 100)->nullable();       // 新宿/池袋/渋谷…など
      $t->string('address', 255)->nullable();
      $t->string('phone', 50)->nullable();
      $t->string('website_url', 2000)->nullable();
      $t->string('map_url', 2000)->nullable();   // Google Maps の共有URLなど
      $t->string('cover_image_path')->nullable();// 代表画像
      $t->json('tags')->nullable();              // ["ラブホ","駅近"] 等
      $t->boolean('is_active')->default(true);
      $t->unsignedSmallInteger('priority')->default(100); // 小さいほど先
      $t->timestamps();

      $t->index(['is_active','area','priority']);
    });
  }
  public function down(): void { Schema::dropIfExists('hotels'); }
};
