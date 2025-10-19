<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('gifts', function (Blueprint $t) {
      $t->id();
      $t->string('name', 120);
      $t->text('description')->nullable();
      $t->string('image_path');             // storage/app/public/gifts/...
      $t->unsignedBigInteger('present_points'); // ユーザーが消費
      $t->unsignedBigInteger('cast_points');    // キャスト受取（present_points 以上は不可）
      $t->boolean('is_active')->default(true);
      $t->unsignedSmallInteger('priority')->default(100); // 小さいほど先
      $t->timestamps();
      $t->index(['is_active', 'priority']);
    });
  }
  public function down(): void {
    Schema::dropIfExists('gifts');
  }
};
