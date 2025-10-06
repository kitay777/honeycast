<?php
// database/migrations/2025_10_06_000001_create_games_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('games', function (Blueprint $t) {
      $t->id();
      $t->string('title');
      $t->string('slug')->unique();
      $t->text('description')->nullable();
      $t->boolean('is_published')->default(true)->index();
      $t->integer('sort_order')->default(0)->index();
      $t->string('file_path');               // storage/app/public/... など
      $t->string('mime_type', 64)->nullable();
      $t->unsignedBigInteger('size')->nullable();   // bytes
      $t->string('poster_path')->nullable();        // サムネ(任意)
      $t->unsignedBigInteger('created_by')->nullable()->index();
      $t->timestamps();
    });
  }
  public function down(): void {
    Schema::dropIfExists('games');
  }
};
