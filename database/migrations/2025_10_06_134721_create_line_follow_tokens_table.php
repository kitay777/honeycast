<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('line_follow_tokens', function (Blueprint $t) {
      $t->id();
      $t->string('token', 64)->unique();
      $t->string('line_user_id', 64)->index();
      $t->timestamp('expires_at');
      $t->timestamp('used_at')->nullable();
      $t->timestamps();
    });
  }
  public function down(): void {
    Schema::dropIfExists('line_follow_tokens');
  }
};
