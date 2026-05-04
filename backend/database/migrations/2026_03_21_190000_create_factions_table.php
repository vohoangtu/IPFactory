<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('universe_id');
            $table->string('name');
            $table->string('culture_bias')->default('conservative');
            $table->unsignedBigInteger('leader_id')->nullable();
            $table->string('color')->nullable();
            $table->timestamps();
            
            $table->foreign('universe_id')->references('id')->on('universes')->onDelete('cascade');
        });

        Schema::create('actor_faction', function (Blueprint $table) {
            $table->unsignedBigInteger('actor_id');
            $table->unsignedBigInteger('faction_id');
            $table->float('loyalty')->default(0.5);
            $table->primary(['actor_id', 'faction_id']);
            
            $table->foreign('actor_id')->references('id')->on('actors')->onDelete('cascade');
            $table->foreign('faction_id')->references('id')->on('factions')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actor_faction');
        Schema::dropIfExists('factions');
    }
};
