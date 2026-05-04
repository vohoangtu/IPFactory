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
            $table->integer('global_tick')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('worlds', function (Blueprint $table) {
            $table->dropColumn('global_tick');
        });
    }
};
