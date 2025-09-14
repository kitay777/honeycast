<?php

// database/migrations/xxxx_create_shop_invite_usages_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('shop_invite_usages', function (Blueprint $t) {
      $t->id();
      $t->foreignId('shop_invite_id')->constrained()->cascadeOnDelete();
      $t->foreignId('user_id')->constrained()->cascadeOnDelete();
      $t->string('ip', 45)->nullable();
      $t->string('user_agent')->nullable();
      $t->timestamps();
      $t->unique(['shop_invite_id', 'user_id']); // 同じ招待を同じ人が複数回計上しない
    });
  }
  public function down(): void { Schema::dropIfExists('shop_invite_usages'); }
};
