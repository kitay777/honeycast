<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_cast_photos_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('cast_photos', function (Blueprint $table) {
      $table->id();
      $table->foreignId('cast_profile_id')->constrained()->cascadeOnDelete();
      $table->string('path');
      $table->unsignedInteger('sort_order')->default(0);
      $table->boolean('is_primary')->default(false);
      $table->timestamps();
      $table->index(['cast_profile_id','sort_order']);
    });
  }
  public function down(): void {
    Schema::dropIfExists('cast_photos');
  }
};

