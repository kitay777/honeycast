<?php

// database/migrations/xxxx_xx_xx_xxxxxx_add_terms_accept_to_users_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('users', function (Blueprint $t) {
      if (!Schema::hasColumn('users','terms_accepted_version')) {
        $t->string('terms_accepted_version')->nullable()->after('remember_token');
      }
      if (!Schema::hasColumn('users','terms_accepted_at')) {
        $t->timestamp('terms_accepted_at')->nullable()->after('terms_accepted_version');
      }
    });
  }
  public function down(): void {
    Schema::table('users', function (Blueprint $t) {
      if (Schema::hasColumn('users','terms_accepted_at')) $t->dropColumn('terms_accepted_at');
      if (Schema::hasColumn('users','terms_accepted_version')) $t->dropColumn('terms_accepted_version');
    });
  }
};

