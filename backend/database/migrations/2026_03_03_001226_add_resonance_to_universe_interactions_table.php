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
        Schema::table('universe_interactions', function (Blueprint $table) {
            $table->float('resonance_level')->default(0);
            $table->float('synchronicity_score')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('universe_interactions', function (Blueprint $table) {
            //
        });
    }
};
