<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('narrative_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('universe_id')->constrained()->cascadeOnDelete();
            $table->string('engine', 64); // event, era, civilization, mythology, religion, causal_trajectory, legend
            $table->json('payload');
            $table->string('status', 32)->default('pending'); // pending, processing, completed, failed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('narrative_jobs');
    }
};
