<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 5: Institution leaders — succession (actor dies, institution persists).
     */
    public function up(): void
    {
        Schema::create('institution_leaders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutional_entities')->cascadeOnDelete();
            $table->foreignId('actor_id')->constrained('actors')->cascadeOnDelete();
            $table->unsignedBigInteger('start_tick');
            $table->unsignedBigInteger('end_tick')->nullable();
            $table->timestamps();

            $table->index('institution_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_leaders');
    }
};
