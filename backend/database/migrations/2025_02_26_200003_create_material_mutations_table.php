<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_mutations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_material_id')->constrained('materials')->cascadeOnDelete();
            $table->foreignId('child_material_id')->constrained('materials')->cascadeOnDelete();
            $table->string('trigger_condition')->nullable(); // e.g. "innovation > 0.8", "epoch > 500"
            $table->json('context_constraint')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_mutations');
    }
};
