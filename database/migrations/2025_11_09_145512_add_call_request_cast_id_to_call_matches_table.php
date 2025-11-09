<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('call_matches', function (Blueprint $table) {
            $table->foreignId('call_request_cast_id')
                  ->nullable()
                  ->after('call_request_id')
                  ->constrained('call_request_casts')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('call_matches', function (Blueprint $table) {
            $table->dropForeign(['call_request_cast_id']);
            $table->dropColumn('call_request_cast_id');
        });
    }
};
