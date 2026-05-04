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
        Schema::create('simulation_tick_manifests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('universe_id')->constrained()->onDelete('cascade');
            $table->integer('tick');
            $table->bigInteger('seed');
            $table->json('engines_ran')->nullable();
            $table->json('engines_skipped')->nullable();
            $table->json('effects')->nullable();
            $table->json('events')->nullable();
            $table->json('state_diff')->nullable();
            $table->float('elapsed_ms')->nullable();
            $table->timestamps();

            $table->index(['universe_id', 'tick']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('simulation_tick_manifests');
    }
};
