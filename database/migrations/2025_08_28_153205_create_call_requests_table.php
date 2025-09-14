// database/migrations/xxxx_xx_xx_create_call_requests_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('call_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // 依頼者
            $table->string('place');                 // 場所
            $table->unsignedTinyInteger('cast_count');      // cast人数
            $table->unsignedTinyInteger('guest_count');     // お客様人数
            $table->string('nomination')->nullable();       // 指名（任意テキスト）
            $table->date('date');                    // 日にち
            $table->time('start_time');              // 開始
            $table->time('end_time');                // 終了
            $table->string('set_plan')->nullable();  // set（例: 1set/2set…）
            $table->string('game_option')->nullable();// ゲームオプション
            $table->text('note')->nullable();
            $table->string('status')->default('pending'); // pending/confirmed/cancelled
            $table->timestamps();
            $table->index(['date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_requests');
    }
};
