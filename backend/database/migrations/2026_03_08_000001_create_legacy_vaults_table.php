<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_vaults', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained()->cascadeOnDelete();
            $table->string('entity_name');
            $table->string('entity_type'); // civilization, hero, relic
            $table->json('legacy_data'); // Final state, achievements, traits
            $table->integer('archived_at_tick');
            $table->float('impact_score')->default(0.0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_vaults');
    }
};
