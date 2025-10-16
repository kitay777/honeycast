<?php

// database/migrations/2025_10_16_000001_create_ad_banners_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('ad_banners', function (Blueprint $t) {
      $t->id();
      $t->string('image_path');       // public/storage 等のパス
      $t->string('url')->nullable();  // クリック先
      $t->unsignedSmallInteger('height')->default(120); // px
      $t->boolean('is_active')->default(true);
      $t->timestamp('starts_at')->nullable();
      $t->timestamp('ends_at')->nullable();
      $t->unsignedSmallInteger('priority')->default(100);
      $t->timestamps();
      $t->index(['is_active','starts_at','ends_at','priority']);
    });
  }
  public function down(): void { Schema::dropIfExists('ad_banners'); }
};
