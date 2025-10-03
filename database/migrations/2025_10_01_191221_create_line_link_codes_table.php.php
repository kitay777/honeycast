<?php

// database/migrations/2025_10_01_000001_create_line_link_codes_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('line_link_codes', function (Blueprint $t) {
      $t->id();
      $t->foreignId('user_id')->constrained()->cascadeOnDelete();
      $t->string('code', 12)->unique();
      $t->timestamp('expires_at');
      $t->timestamp('used_at')->nullable();
      $t->timestamps();
      $t->index(['user_id','expires_at']);
    });
  }
  public function down(): void {
    Schema::dropIfExists('line_link_codes');
  }
};
