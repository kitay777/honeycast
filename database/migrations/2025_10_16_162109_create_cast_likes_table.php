<?php

// database/migrations/2025_10_16_210000_create_cast_likes_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('cast_likes', function (Blueprint $t) {
      $t->id();
      $t->foreignId('user_id')->constrained()->cascadeOnDelete();
      $t->foreignId('cast_profile_id')->constrained('cast_profiles')->cascadeOnDelete();
      $t->timestamps();
      $t->unique(['user_id','cast_profile_id']);
      $t->index('cast_profile_id');
    });
  }
  public function down(): void {
    Schema::dropIfExists('cast_likes');
  }
};
