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
        Schema::create('legendary_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('universe_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('original_agent_id');
            $table->string('name');
            $table->string('archetype');
            $table->json('fate_tags');
            $table->text('biography')->nullable();
            $table->string('image_url')->nullable();
            $table->unsignedBigInteger('tick_discovered');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legendary_agents');
    }
};
