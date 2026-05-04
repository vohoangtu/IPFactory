<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actor_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('achievement_id')->constrained('achievements')->cascadeOnDelete();
            $table->foreignId('actor_id')->constrained('actors')->cascadeOnDelete();
            $table->timestamp('unlocked_at')->nullable();
            $table->timestamps();

            $table->unique(['achievement_id', 'actor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actor_achievements');
    }
};
