<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('universe_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('achievement_id')->constrained('achievements')->cascadeOnDelete();
            $table->foreignId('universe_id')->constrained('universes')->cascadeOnDelete();
            $table->timestamp('unlocked_at')->nullable();
            $table->timestamps();

            $table->unique(['achievement_id', 'universe_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('universe_achievements');
    }
};
