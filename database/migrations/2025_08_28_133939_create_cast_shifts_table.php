<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('cast_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cast_profile_id')->constrained()->cascadeOnDelete();
            $table->date('date');                 // 2025-08-28
            $table->time('start_time');           // 20:00:00
            $table->time('end_time');             // 00:00:00（翌日に跨る場合は end_date 追加でもOK）
            $table->boolean('is_reserved')->default(false);
            $table->timestamps();

            $table->unique(['cast_profile_id','date','start_time']);
            $table->index(['date']);
        });
    }
    public function down(): void { Schema::dropIfExists('cast_shifts'); }
};