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
        // Update vocation_definitions to align with V1 Entity
        Schema::table('vocation_definitions', function (Blueprint $table) {
            // Rename columns or add missing ones
            if (Schema::hasColumn('vocation_definitions', 'min_tier')) {
                $table->renameColumn('min_tier', 'tier');
            } else {
                $table->integer('tier')->default(1);
            }
            
            if (!Schema::hasColumn('vocation_definitions', 'element_affinity')) {
                $table->jsonb('element_affinity')->nullable();
            }
            
            if (!Schema::hasColumn('vocation_definitions', 'evolves_to')) {
                $table->string('evolves_to')->nullable(); // Can be an ID or JSON array
            }

            // Remove non-V1 columns if they are not needed anymore (but maybe keep for backward compatibility)
            // motivation_profile, tags are still there from previous migrations
        });

        // Create skills table
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('vocation_id');
            $table->string('name');
            $table->jsonb('element');
            $table->integer('cost')->default(0);
            $table->text('rule_dsl');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->foreign('vocation_id')->references('id')->on('vocation_definitions')->onDelete('cascade');
        });

        // Create actor_mastery table
        Schema::create('actor_mastery', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actor_id');
            $table->string('vocation_id');
            $table->integer('level')->default(1);
            $table->float('experience')->default(0.0);
            $table->timestamps();

            $table->foreign('actor_id')->references('id')->on('actors')->onDelete('cascade');
            $table->foreign('vocation_id')->references('id')->on('vocation_definitions')->onDelete('cascade');
            $table->unique(['actor_id', 'vocation_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actor_mastery');
        Schema::dropIfExists('skills');
        
        Schema::table('vocation_definitions', function (Blueprint $table) {
            if (Schema::hasColumn('vocation_definitions', 'tier')) {
                $table->renameColumn('tier', 'min_tier');
            }
            $table->dropColumn(['element_affinity', 'evolves_to']);
        });
    }
};
