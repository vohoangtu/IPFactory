<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ruleset_combine_rules', function (Blueprint $table) {
            $table->id();
            $table->string('ruleset_a_id');
            $table->string('ruleset_b_id');
            $table->string('conflict_strategy')->default('stricter_wins');
            $table->jsonb('dimension_overrides')->nullable();
            $table->string('hybrid_outcome_id')->nullable();
            $table->timestamps();

            // Foreign keys to ruleset_definitions (manual since they are strings)
            // No strict foreign key to allow flexible seeding
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ruleset_combine_rules');
    }
};
