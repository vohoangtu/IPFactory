<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Phase 3: Artifact layer — book, poem, painting, law, religion, theory, architecture, music.
     */
    public function up(): void
    {
        Schema::create('artifacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('universe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('creator_actor_id')->nullable()->constrained('actors')->nullOnDelete();
            $table->foreignId('institution_id')->nullable()->constrained('institutional_entities')->nullOnDelete();
            $table->string('artifact_type', 64); // book, poem, painting, law, religion, theory, architecture, music
            $table->string('title')->nullable();
            $table->string('theme')->nullable();
            $table->string('culture')->nullable();
            $table->unsignedBigInteger('tick_created');
            $table->float('impact_score')->default(0.5);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['universe_id', 'creator_actor_id']);
            $table->index(['universe_id', 'tick_created']);
            $table->index(['universe_id', 'artifact_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artifacts');
    }
};
