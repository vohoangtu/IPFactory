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
        Schema::create('universe_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('universe_a_id')->constrained('universes')->onDelete('cascade');
            $table->foreignId('universe_b_id')->constrained('universes')->onDelete('cascade');
            $table->string('interaction_type'); // resonance, collision, trade
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['universe_a_id', 'interaction_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('universe_interactions');
    }
};
