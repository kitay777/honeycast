<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // クーポンコード
            $table->string('name'); // 名称
            $table->integer('discount_points')->default(0); // 付与ポイント数
            $table->integer('max_uses')->nullable(); // 使用上限（null=無制限）
            $table->timestamp('expires_at')->nullable(); // 有効期限
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
