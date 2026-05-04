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
        Schema::create('diplomatic_treaties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('universe_id')->constrained()->cascadeOnDelete();
            
            $table->unsignedBigInteger('source_civ_id');
            $table->unsignedBigInteger('target_civ_id');
            
            $table->string('treaty_type');
            $table->json('terms')->nullable();
            
            $table->unsignedBigInteger('started_at_tick');
            $table->unsignedBigInteger('ends_at_tick')->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();

            $table->index(['universe_id', 'source_civ_id', 'target_civ_id'], 'idx_civ_pair');
            $table->index(['universe_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diplomatic_treaties');
    }
};
