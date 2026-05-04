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
        Schema::create('narrative_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('universe_id')->constrained()->onDelete('cascade');
            $table->string('current_arc')->default('Genesis');
            $table->json('active_conflicts')->nullable();
            $table->json('dominant_ideologies')->nullable();
            $table->integer('last_tick')->default(0);
            $table->timestamps();
            
            $table->index('universe_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('narrative_states');
    }
};
