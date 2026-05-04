<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('world_ruleset_runtime', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained()->onDelete('cascade');
            $table->string('ruleset_id');
            $table->bigInteger('active_tick')->default(0);
            $table->jsonb('ambient_energy')->nullable();
            $table->float('reality_stability')->default(1.0);
            $table->jsonb('dynamic_axioms')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('world_ruleset_runtime');
    }
};
