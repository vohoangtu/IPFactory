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
        Schema::create('visual_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legendary_agent_id')->constrained('legendary_agents')->onDelete('cascade');
            $table->unsignedBigInteger('parent_branch_id')->nullable();
            $table->json('visual_dna')->nullable(); // Root DNA snapshot for this branch
            $table->unsignedBigInteger('fork_tick');
            $table->string('fork_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visual_branches');
    }
};
