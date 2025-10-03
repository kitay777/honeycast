<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    // ★ 既にあるなら何もしない → マイグレーションは成功として記録される
    if (Schema::hasTable('cast_profile_tag')) {
      // 必要なら、ここで不足カラム/インデックスを追加する Schema::table(...) を書けます
      return;
    }

    Schema::create('cast_profile_tag', function (Blueprint $t) {
      $t->id();
      $t->foreignId('cast_profile_id')->constrained()->cascadeOnDelete();
      $t->foreignId('tag_id')->constrained()->cascadeOnDelete();
      $t->timestamps();
      $t->unique(['cast_profile_id','tag_id']);
      $t->index(['tag_id','cast_profile_id']);
    });
  }

  public function down(): void {
    Schema::dropIfExists('cast_profile_tag');
  }
};
