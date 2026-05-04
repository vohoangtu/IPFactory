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
        Schema::create('myth_scars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('universe_id')->constrained('universes')->onDelete('cascade');
            $table->string('zone_id'); // Can be string or integer depends on Rust engine
            $table->string('name');
            $table->text('description')->nullable();
            $table->float('severity')->default(0.5); // 0.0 -> 1.0
            $table->float('decay_rate')->default(0.01);
            $table->unsignedBigInteger('created_at_tick');
            $table->unsignedBigInteger('resolved_at_tick')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('myth_scars');
    }
};
