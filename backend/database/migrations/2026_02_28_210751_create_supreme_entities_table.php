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
        Schema::create('supreme_entities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('universe_id')->constrained()->cascadeOnDelete();
            
            // Core Identity
            $table->string('name');
            $table->enum('entity_type', ['world_will', 'deity', 'primordial_beast', 'outer_god']);
            $table->string('domain')->nullable(); // Optional specific law/domain (e.g. 'Reincarnation')
            $table->text('description')->nullable();
            
            // Stats & Influence
            $table->float('power_level')->default(0.1); // Scalar magnitude of power
            $table->json('alignment')->nullable(); // Vector direction (Spirituality, Hardtech, etc)
            $table->enum('status', ['dormant', 'active', 'sealed', 'fallen'])->default('active');
            
            // Record keeping
            $table->integer('ascended_at_tick')->nullable();
            $table->integer('fallen_at_tick')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supreme_entities');
    }
};
