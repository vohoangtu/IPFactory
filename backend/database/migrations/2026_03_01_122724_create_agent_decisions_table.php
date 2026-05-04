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
        Schema::create('agent_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->constrained()->onDelete('cascade');
            $table->foreignId('universe_id')->constrained()->onDelete('cascade');
            $table->integer('tick');
            $table->string('action_type');
            $table->unsignedBigInteger('target_id')->nullable();
            $table->float('utility_score');
            $table->json('traits_snapshot');
            $table->json('context_snapshot')->nullable();
            $table->timestamps();

            $table->index(['actor_id', 'tick']);
            $table->index(['universe_id', 'tick']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_decisions');
    }
};
