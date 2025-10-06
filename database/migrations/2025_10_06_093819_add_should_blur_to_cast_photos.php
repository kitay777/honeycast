<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cast_photos', function (Blueprint $table) {
            // 既にあればスキップ
            if (!Schema::hasColumn('cast_photos', 'should_blur')) {
                // is_primary の後ろに boolean を追加（既存行は default の false が入る）
                $table->boolean('should_blur')->default(false)->after('is_primary');
            }
        });

        // 念のため null があれば false で埋める（環境差対策）
        DB::table('cast_photos')->whereNull('should_blur')->update(['should_blur' => false]);
    }

    public function down(): void
    {
        Schema::table('cast_photos', function (Blueprint $table) {
            if (Schema::hasColumn('cast_photos', 'should_blur')) {
                $table->dropColumn('should_blur');
            }
        });
    }
};
