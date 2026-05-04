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
        Schema::create('extradimensional_relics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained()->onDelete('cascade');
            $table->integer('origin_universe_id')->nullable();
            $table->string('name');
            $table->string('rarity')->default('common'); // common, rare, epic, legendary, mythic, void
            $table->json('power_vector')->nullable(); // Impact on simulation metrics
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extradimensional_relics');
    }
};
