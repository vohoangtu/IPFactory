<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('achievement_id')->constrained('achievements')->cascadeOnDelete();
            $table->foreignId('universe_id')->nullable()->constrained('universes')->nullOnDelete();
            $table->timestamp('claimed_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'achievement_id', 'universe_id']);
            $table->index(['user_id', 'universe_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_claims');
    }
};
