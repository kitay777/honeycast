<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();        // 予約者（顧客）
            $table->foreignId('cast_profile_id')->constrained()->cascadeOnDelete();// 指名キャスト
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status')->default('pending'); // pending/confirmed/cancelled
            $table->string('payment_method')->nullable(); // 'card','cash' など
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['cast_profile_id','date']);
        });
    }
    public function down(): void { Schema::dropIfExists('reservations'); }
};
