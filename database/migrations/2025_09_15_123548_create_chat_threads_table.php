<?php

// database/migrations/2025_09_15_000000_create_chat_threads_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('chat_threads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_one_id'); // 小さい方
            $table->unsignedBigInteger('user_two_id'); // 大きい方
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->unique(['user_one_id','user_two_id']);
            $table->foreign('user_one_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('user_two_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_thread_id');
            $table->unsignedBigInteger('sender_id');
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('chat_thread_id')->references('id')->on('chat_threads')->cascadeOnDelete();
            $table->foreign('sender_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index('created_at');
        });
    }

    public function down(): void {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_threads');
    }
};
