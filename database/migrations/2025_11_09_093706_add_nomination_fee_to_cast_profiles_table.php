<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cast_profiles', function (Blueprint $table) {
            $table->unsignedInteger('nomination_fee')->default(0)->after('freeword')
                ->comment('指名料（円単位）');
        });
    }

    public function down(): void
    {
        Schema::table('cast_profiles', function (Blueprint $table) {
            $table->dropColumn('nomination_fee');
        });
    }
};
