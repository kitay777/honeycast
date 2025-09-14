<?php

// database/migrations/xxxx_add_invite_columns_to_call_request_casts.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('call_request_casts', function (Blueprint $t) {
      if (!Schema::hasColumn('call_request_casts','invited_at')) {
        $t->timestamp('invited_at')->nullable()->after('assigned_by');
      }
      if (!Schema::hasColumn('call_request_casts','responded_at')) {
        $t->timestamp('responded_at')->nullable()->after('invited_at');
      }
      // status は既存を流用: invited | accepted | declined | confirmed | canceled
    });
  }
  public function down(): void {
    Schema::table('call_request_casts', function (Blueprint $t) {
      $t->dropColumn(['invited_at','responded_at']);
    });
  }
};
