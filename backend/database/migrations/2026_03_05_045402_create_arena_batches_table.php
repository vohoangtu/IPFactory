<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arena_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedInteger('generation')->default(1);
            $table->unsignedInteger('universe_count')->default(10);
            $table->unsignedInteger('ticks_per_universe')->default(2000);
            $table->enum('status', ['pending', 'running', 'evaluating', 'completed'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arena_batches');
    }
};
