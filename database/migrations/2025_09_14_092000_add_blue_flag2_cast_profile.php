<?php

// database/migrations/2025_09_14_000001_add_blur_flag_to_cast_profiles.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('cast_profiles', function (Blueprint $table) {
            $table->boolean('is_blur_default')->default(true)->after('photo_path'); // 既定でぼかすか
        });
    }
    public function down(): void {
        Schema::table('cast_profiles', function (Blueprint $table) {
            $table->dropColumn('is_blur_default');
        });
    }
};

