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
        Schema::table('universes', function (Blueprint $table) {
            $table->float('observation_load')->default(0); // Quantum pressure from gaze
            $table->timestamp('last_observed_at')->nullable(); // Last time the Architect looked
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('universes', function (Blueprint $table) {
            //
        });
    }
};
