<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_prohibited_words_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('prohibited_words', function (Blueprint $t) {
      $t->id();
      $t->string('phrase');                       // 表示/編集用の元フレーズ
      $t->string('normalized')->index();         // 照合用（小文字/半角/スペース除去など）
      $t->enum('match_type', ['contain','exact','regex'])->default('contain');
      $t->enum('severity', ['block','warn','mask'])->default('block'); // まずは block で運用
      $t->boolean('is_active')->default(true);
      $t->string('replacement')->nullable();     // mask 用（将来）
      $t->string('note')->nullable();
      $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
      $t->timestamps();
      $t->unique(['normalized','match_type']);   // 同一照合キーの重複を防ぐ
    });
  }
  public function down(): void {
    Schema::dropIfExists('prohibited_words');
  }
};

