<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('worlds', function (Blueprint $table) {
            $table->bigInteger('global_tick')->change();
        });

        Schema::table('universes', function (Blueprint $table) {
            $table->bigInteger('current_tick')->change();
        });
    }

    public function down(): void
    {
        Schema::table('worlds', function (Blueprint $table) {
            $table->integer('global_tick')->change();
        });

        Schema::table('universes', function (Blueprint $table) {
            $table->integer('current_tick')->change();
        });
    }
};
