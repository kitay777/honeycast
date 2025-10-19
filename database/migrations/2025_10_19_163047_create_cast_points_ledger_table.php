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
  Schema::create('cast_points_ledger', function (Blueprint $t) {
    $t->id();
    $t->foreignId('cast_profile_id')->constrained()->cascadeOnDelete();
    $t->bigInteger('delta');
    $t->bigInteger('balance_after');
    $t->string('reason',255)->nullable();
    $t->foreignId('acted_by')->nullable()->constrained('users')->nullOnDelete();
    $t->timestamps();
    $t->index(['cast_profile_id','created_at']);
  });
}
public function down(): void {
  Schema::dropIfExists('cast_points_ledger');
}

};
