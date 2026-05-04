<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institutional_entities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('universe_id')->constrained()->cascadeOnDelete();
            
            $table->string('name');
            $table->string('entity_type'); // faction, guild, order, cult, etc.
            
            // Stats (§4.5)
            $table->json('ideology_vector')->nullable(); // CZ-like vector
            $table->float('org_capacity')->default(1.0);
            $table->float('institutional_memory')->default(1.0);
            $table->float('legitimacy')->default(1.0);
            
            // Spatial Influence (§4.5)
            $table->json('influence_map')->nullable(); // {zone_id: influence_level}
            
            $table->integer('spawned_at_tick');
            $table->integer('collapsed_at_tick')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institutional_entities');
    }
};
