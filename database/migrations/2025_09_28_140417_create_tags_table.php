<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_tags_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('tags', function (Blueprint $t) {
      $t->id();
      $t->string('name')->unique();
      $t->string('slug')->nullable()->unique(); // ← NULL可 + UNIQUE（空文字は入れない方針）
      $t->boolean('is_active')->default(true);
      $t->unsignedInteger('sort_order')->default(0);
      $t->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists('tags'); }
};
