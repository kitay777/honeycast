<?php

// database/migrations/2025_09_14_092439_create_cast_profile_view_permissions_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('cast_profile_view_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cast_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('viewer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('granted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['pending','approved','denied'])->default('pending');
            $table->text('message')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            // ★ ここを短い名前に
            $table->unique(['cast_profile_id','viewer_user_id'], 'cpvp_cast_viewer_uidx');

            // これもギリギリなので短い名前にしておく（任意だけど安全）
            $table->index(['cast_profile_id','status'], 'cpvp_cast_status_idx');
        });
    }

    public function down(): void {
        Schema::dropIfExists('cast_profile_view_permissions');
    }
};
