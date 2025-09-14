<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('shops', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->string('code')->unique();         // 店舗コード/スラッグ
      $table->string('contact_email')->nullable();
      $table->string('contact_phone')->nullable();
      $table->boolean('is_active')->default(true);
      $table->text('note')->nullable();
      $table->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists('shops'); }
};
