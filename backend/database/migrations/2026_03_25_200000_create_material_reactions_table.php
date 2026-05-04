<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create the new reactions table
        Schema::create('material_reactions', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique(); // Unique identifier for the reaction
            $table->string('name')->nullable();
            
            // Multi-input / Multi-output using JSON
            // Structure: {"quang-nang-co-dai": 2, "source_energy": 1}
            $table->json('inputs'); 
            
            // Structure: {"quang-nang-vinh-cuu": 1}
            $table->json('outputs'); 
            
            // DSL Condition for RuleVM
            $table->text('condition')->nullable(); 
            
            // Emergence Factors
            $table->float('rate')->default(1.0); // Probability [0-1]
            $table->float('energy_cost')->default(0.0);
            $table->float('entropy_produced')->default(0.0);
            
            $table->timestamps();
        });

        // 2. We keep the old table for now to avoid breaking existing code 
        // until we refactor the models and services fully.
    }

    public function down(): void
    {
        Schema::dropIfExists('material_reactions');
    }
};
