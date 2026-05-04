<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 4: School layer — founder, idea, members, influence, status.
     */
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('universe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('founder_actor_id')->nullable()->constrained('actors')->nullOnDelete();
            $table->foreignId('idea_id')->constrained('ideas')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('members')->default(0);
            $table->float('influence')->default(0.1);
            $table->string('status', 32)->nullable(); // emerging, growing, dominant, declining, collapsed
            $table->timestamps();

            $table->index(['universe_id', 'idea_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
