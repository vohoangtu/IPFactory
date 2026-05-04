<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Civilization attractors: activation_rules + force_map for event probability modulation.
     */
    public function up(): void
    {
        Schema::create('civilization_attractors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('activation_rules')->nullable()->comment('Same format as threshold_rules: key, op, value');
            $table->json('force_map')->nullable()->comment('event_type => weight for probability modulation');
            $table->float('decay_rate')->default(0.02);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('civilization_attractors');
    }
};
