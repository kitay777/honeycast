<?php

// database/migrations/2025_09_28_000100_create_cast_photo_view_permissions_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('cast_photo_view_permissions', function (Blueprint $table) {
      $table->id();
      $table->foreignId('cast_photo_id')->constrained()->cascadeOnDelete();
      $table->foreignId('viewer_user_id')->constrained('users')->cascadeOnDelete();
      $table->foreignId('granted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
      $table->enum('status', ['pending','approved','denied'])->default('pending');
      $table->text('message')->nullable();
      $table->timestamp('expires_at')->nullable();
      $table->timestamps();
      // 短いユニーク名
      $table->unique(['cast_photo_id','viewer_user_id'], 'cp_photo_viewer_uidx');
      $table->index(['cast_photo_id','status'], 'cp_photo_status_idx');
    });
  }
  public function down(): void {
    Schema::dropIfExists('cast_photo_view_permissions');
  }
};

