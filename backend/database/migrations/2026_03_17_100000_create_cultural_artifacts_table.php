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
        Schema::create('cultural_artifacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('universe_id')->constrained()->cascadeOnDelete();
            
            $table->unsignedBigInteger('civ_id')->index(); // Faction / Civilization
            $table->unsignedBigInteger('author_id')->nullable()->index(); // Actor ID
            
            $table->string('name');
            $table->string('type'); // ART, LITERATURE, TABOO, RITUAL, NORM
            
            $table->float('power_level')->default(1.0);
            $table->json('properties')->nullable();
            
            $table->unsignedBigInteger('created_at_tick');
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();

            $table->index(['universe_id', 'civ_id', 'type'], 'idx_civ_culture');
            $table->index(['universe_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cultural_artifacts');
    }
};
