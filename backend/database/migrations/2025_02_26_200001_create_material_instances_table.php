<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->foreignId('universe_id')->constrained('universes')->cascadeOnDelete();
            $table->string('lifecycle')->default('dormant');
            $table->unsignedBigInteger('activated_at_tick')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();
        });

        Schema::table('material_instances', function (Blueprint $table) {
            $table->index(['universe_id', 'lifecycle']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_instances');
    }
};
