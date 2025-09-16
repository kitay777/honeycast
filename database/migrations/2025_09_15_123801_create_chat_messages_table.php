<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('chat_messages')) {
            Schema::create('chat_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('chat_thread_id');
                $table->unsignedBigInteger('sender_id');
                $table->text('body');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->index(['chat_thread_id','created_at']);
                // 外部キーは既にある環境/ない環境が混在しやすいので、
                // ここでは create 時だけ付与。必要なら別の専用マイグレーションで追加してOK。
                $table->foreign('chat_thread_id')->references('id')->on('chat_threads')->cascadeOnDelete();
                $table->foreign('sender_id')->references('id')->on('users')->cascadeOnDelete();
            });
        } else {
            // 既存テーブルに不足カラムがあればここで足す（任意）
            if (!Schema::hasColumn('chat_messages', 'read_at')) {
                Schema::table('chat_messages', function (Blueprint $table) {
                    $table->timestamp('read_at')->nullable()->after('body');
                });
            }
            // 外部キーや index を同期したい場合は、別マイグレーションで明示的に追加するのが安全です
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
