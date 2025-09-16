<?php

// database/migrations/xxxx_xx_xx_xxxxxx_add_is_cast_to_users_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_cast')) {
                $table->boolean('is_cast')->default(false)->index()->after('email_verified_at');
            }
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_cast')) {
                $table->dropIndex(['is_cast']);
                $table->dropColumn('is_cast');
            }
        });
    }
};
