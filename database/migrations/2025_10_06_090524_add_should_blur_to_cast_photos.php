<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cast_photos', function (Blueprint $table) {
            //
            $table->boolean('should_blur')->default(false)->after('is_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cast_photos', function (Blueprint $table) {
            //
            $table->dropColumn('should_blur');
        });
    }
};
