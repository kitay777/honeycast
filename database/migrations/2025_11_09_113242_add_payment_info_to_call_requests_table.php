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
Schema::table('call_requests', function (Blueprint $table) {
    $table->enum('payment_method', ['cash', 'credit'])->nullable()->after('status');
    $table->timestamp('executed_at')->nullable()->after('payment_method');
    $table->unsignedInteger('final_price')->nullable()->after('executed_at');
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('call_requests', function (Blueprint $table) {
            $table->dropColumn('payment_method');
            $table->dropColumn('executed_at');
            $table->dropColumn('final_price');  
        });
    }
};
