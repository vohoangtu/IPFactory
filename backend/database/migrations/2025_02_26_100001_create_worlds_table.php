<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worlds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('multiverse_id')->nullable()->constrained('multiverses')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->json('axiom')->nullable(); // physical/magic constants, tech ceiling, archetypes
            $table->json('world_seed')->nullable(); // WorldSeed preset
            $table->string('origin')->default('generic'); // Vietnamese, European, Futuristic...
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worlds');
    }
};
