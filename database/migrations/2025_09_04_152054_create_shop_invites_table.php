<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('shop_invites', function (Blueprint $table) {
      $table->id();
      $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
      $table->string('token', 64)->unique();
      $table->timestamp('expires_at')->nullable();
      $table->unsignedInteger('max_uses')->nullable();  // null=無制限
      $table->unsignedInteger('used_count')->default(0);
      $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
      $table->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists('shop_invites'); }
};
