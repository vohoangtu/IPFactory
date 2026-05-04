<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ruleset_definitions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('extends')->nullable();
            $table->smallInteger('tier')->default(0);
            $table->string('tier_label', 50)->nullable();
            $table->integer('priority')->default(50);
            $table->float('weight')->default(1.0);
            $table->jsonb('tags')->nullable();
            
            // 9-Dimension Ruleset
            $table->jsonb('physics')->nullable();
            $table->jsonb('energy_systems')->nullable();
            $table->jsonb('metaphysics')->nullable();
            $table->jsonb('power_law')->nullable();
            $table->jsonb('social_constraints')->nullable();
            $table->jsonb('emergence_rules')->nullable();
            $table->jsonb('knowledge_system')->nullable();
            $table->jsonb('individual_access')->nullable();
            $table->jsonb('temporal_dynamics')->nullable();
            
            // Tier Lifecycle
            $table->jsonb('tier_ceiling')->nullable();
            $table->jsonb('tier_floor')->nullable();
            $table->jsonb('ascension_conditions')->nullable();
            $table->jsonb('descent_conditions')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ruleset_definitions');
    }
};
