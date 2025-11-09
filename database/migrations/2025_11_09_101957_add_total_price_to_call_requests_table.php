<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('call_requests', function (Blueprint $table) {
            $table->unsignedInteger('total_price')->default(0)->after('coupon_id')->comment('合計金額（円）');
        });
    }

    public function down(): void {
        Schema::table('call_requests', function (Blueprint $table) {
            $table->dropColumn('total_price');
        });
    }
};
