<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('call_request_casts', function (Blueprint $table) {
      $table->id();
      $table->foreignId('call_request_id')->constrained('call_requests')->cascadeOnDelete();
      $table->foreignId('cast_profile_id')->constrained('cast_profiles')->cascadeOnDelete();
      $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
      $table->string('status', 20)->default('assigned'); // assigned|confirmed|canceled
      $table->text('note')->nullable();
      $table->timestamps();

      $table->unique(['call_request_id','cast_profile_id']);
    });
  }
  public function down(): void { Schema::dropIfExists('call_request_casts'); }
};
