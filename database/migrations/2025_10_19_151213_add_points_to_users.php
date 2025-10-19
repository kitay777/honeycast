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
    Schema::table('users', function (Blueprint $t) {
        $t->bigInteger('points')->default(0)->after('password');
        $t->index('points');
    });
}
public function down(): void {
    Schema::table('users', function (Blueprint $t) {
        $t->dropColumn('points');
    });
}

};
