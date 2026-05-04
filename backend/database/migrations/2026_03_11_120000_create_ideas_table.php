<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 4: Idea layer — origin_actor, artifact, theme, influence, followers.
     */
    public function up(): void
    {
        Schema::create('ideas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('universe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('origin_actor_id')->nullable()->constrained('actors')->nullOnDelete();
            $table->foreignId('artifact_id')->nullable()->constrained('artifacts')->nullOnDelete();
            $table->string('theme')->nullable();
            $table->float('influence_score')->default(0.1);
            $table->float('followers')->default(0);
            $table->unsignedBigInteger('birth_tick');
            $table->timestamps();

            $table->index(['universe_id', 'origin_actor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ideas');
    }
};
