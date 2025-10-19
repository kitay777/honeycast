<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void {
    Schema::create('points_ledger', function (Blueprint $t) {
        $t->id();
        $t->foreignId('user_id')->constrained()->cascadeOnDelete();
        $t->bigInteger('delta');                 // 増減(+/-)
        $t->bigInteger('balance_after');         // 操作後残高
        $t->string('reason', 255)->nullable();   // 理由（メモ）
        $t->foreignId('acted_by')->nullable()->constrained('users')->nullOnDelete(); // 操作ユーザー
        $t->timestamps();
        $t->index(['user_id','created_at']);
    });
}
public function down(): void {
    Schema::dropIfExists('points_ledger');
}

};
