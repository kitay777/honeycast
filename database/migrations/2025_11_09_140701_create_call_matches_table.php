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
Schema::create('call_matches', function (Blueprint $table) {
    $table->id();
    $table->foreignId('call_request_id')->nullable()->constrained()->cascadeOnDelete();
    $table->foreignId('cast_profile_id')->constrained()->cascadeOnDelete();
    $table->enum('status', ['started','ended'])->default('started');
    $table->unsignedInteger('duration_minutes')->default(60); // 60, 120, 180
    $table->timestamp('started_at')->nullable();
    $table->timestamp('ended_at')->nullable();
    $table->decimal('latitude', 10, 7)->nullable();
    $table->decimal('longitude', 10, 7)->nullable();
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_matches');
    }
};
