<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beliefs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // Religion, Ideology, Cult
            $table->json('trait_weights'); // 17D weights for ideal profile
            $table->timestamps();
        });

        Schema::create('actor_beliefs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->constrained()->onDelete('cascade');
            $table->foreignId('belief_id')->constrained()->onDelete('cascade');
            $table->float('alignment')->default(0); // 0.0 to 1.0
            $table->timestamps();

            $table->unique(['actor_id', 'belief_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actor_beliefs');
        Schema::dropIfExists('beliefs');
    }
};
