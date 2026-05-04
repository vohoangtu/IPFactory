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
        Schema::create('narrative_feedback_signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('universe_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('apply_at_tick');
            $table->string('type', 32)->default('omen'); // omen, plot, attractor, emotion
            $table->json('payload');
            $table->string('status', 16)->default('pending'); // pending, applied, failed
            $table->timestamps();
            
            $table->index(['universe_id', 'apply_at_tick', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('narrative_feedback_signals');
    }
};
