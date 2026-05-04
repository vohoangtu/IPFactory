<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attractor_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('universe_id')->constrained('universes')->cascadeOnDelete();
            $table->string('attractor_type')->comment('Matches civilization_attractors.name');
            $table->float('strength')->default(1.0);
            $table->json('state_json')->nullable();
            $table->unsignedBigInteger('spawned_by')->nullable()->comment('Parent attractor_instance id');
            $table->unsignedBigInteger('created_tick');
            $table->unsignedBigInteger('expires_tick')->nullable();
            $table->timestamps();
            $table->index(['universe_id', 'created_tick']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attractor_instances');
    }
};
