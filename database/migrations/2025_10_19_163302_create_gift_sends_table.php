<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void {
  Schema::create('gift_sends', function (Blueprint $t) {
    $t->id();
    $t->foreignId('sender_user_id')->constrained('users')->cascadeOnDelete();
    $t->foreignId('cast_profile_id')->constrained()->cascadeOnDelete();
    $t->foreignId('gift_id')->constrained('gifts')->cascadeOnDelete();
    $t->bigInteger('present_points'); // 消費
    $t->bigInteger('cast_points');    // 受取
    $t->string('message', 200)->nullable();
    $t->timestamps();
    $t->index(['sender_user_id','cast_profile_id','created_at']);
    $t->index(['gift_id','created_at']);
  });
}
public function down(): void { Schema::dropIfExists('gift_sends'); }

};
