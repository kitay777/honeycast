<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::table('cast_profiles', function (Blueprint $table) {
        $table->integer('rating')->nullable()->change();
    });
}

public function down()
{
    Schema::table('cast_profiles', function (Blueprint $table) {
        $table->tinyInteger('rating')->nullable()->change();
    });
}

};
