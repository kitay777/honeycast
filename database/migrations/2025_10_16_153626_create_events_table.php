<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('events', function (Blueprint $t) {
      $t->id();
      $t->string('title', 200);
      $t->text('body')->nullable();
      $t->string('place', 200)->nullable();
      $t->timestamp('starts_at');
      $t->timestamp('ends_at')->nullable();
      $t->boolean('is_all_day')->default(false);
      $t->boolean('is_active')->default(true);
      $t->unsignedSmallInteger('priority')->default(100);
      $t->string('image_path')->nullable(); // 任意/バナー等
      $t->timestamps();

      $t->index(['is_active','starts_at','priority']);
    });
  }
  public function down(): void { Schema::dropIfExists('events'); }
};
