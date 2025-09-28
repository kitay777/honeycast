<?php
// database/migrations/xxxx_xx_xx_xxxxxx_add_is_blur_default_to_cast_photos_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('cast_photos', function (Blueprint $table) {
            if (!Schema::hasColumn('cast_photos', 'is_blur_default')) {
                $table->boolean('is_blur_default')->default(true)->after('path');
            }
        });
    }
    public function down(): void {
        Schema::table('cast_photos', function (Blueprint $table) {
            if (Schema::hasColumn('cast_photos', 'is_blur_default')) {
                $table->dropColumn('is_blur_default');
            }
        });
    }
};
