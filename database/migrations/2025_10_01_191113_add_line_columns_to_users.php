<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            if (!Schema::hasColumn('users', 'line_user_id')) {
                $t->string('line_user_id')->nullable()->unique();
            }
            if (!Schema::hasColumn('users', 'line_display_name')) {
                $t->string('line_display_name')->nullable();
            }
            if (!Schema::hasColumn('users', 'line_opt_in_at')) {
                $t->timestamp('line_opt_in_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            if (Schema::hasColumn('users', 'line_opt_in_at')) {
                $t->dropColumn('line_opt_in_at');
            }
            if (Schema::hasColumn('users', 'line_display_name')) {
                $t->dropColumn('line_display_name');
            }
            if (Schema::hasColumn('users', 'line_user_id')) {
                // 既に他のマイグレーションで使っている可能性があるなら、dropは任意
                $t->dropColumn('line_user_id');
            }
        });
    }
};
