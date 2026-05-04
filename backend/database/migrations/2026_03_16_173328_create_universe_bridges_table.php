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
        Schema::create('universe_bridges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_universe_id')->constrained('universes')->onDelete('cascade');
            $table->foreignId('target_universe_id')->constrained('universes')->onDelete('cascade');
            $table->string('bridge_type')->default('causal'); // 'causal', 'resonance', 'bleed'
            $table->float('resonance_level')->default(0.1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['source_universe_id', 'target_universe_id'], 'unique_universe_bridge');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('universe_bridges');
    }
};
