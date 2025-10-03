<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_cast_profile_tag_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('cast_profile_tag', function (Blueprint $t) {
      $t->id();
      $t->foreignId('cast_profile_id')->constrained()->cascadeOnDelete();
      $t->foreignId('tag_id')->constrained()->cascadeOnDelete();
      $t->timestamps();
      $t->unique(['cast_profile_id','tag_id']);
      $t->index(['tag_id','cast_profile_id']);
    });
  }
  public function down(): void { Schema::dropIfExists('cast_profile_tag'); }
};
