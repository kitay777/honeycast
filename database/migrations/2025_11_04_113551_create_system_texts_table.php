<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_texts', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // 'individual', 'group', 'chokkotto', 'delivery'
            $table->longText('content'); // HTML or Markdown
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_texts');
    }
};
