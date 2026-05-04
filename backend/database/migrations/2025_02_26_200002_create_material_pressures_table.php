<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_pressures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->string('vector_key'); // entropy, order, innovation, growth, trauma...
            $table->decimal('coefficient', 10, 6)->default(0);
            $table->timestamps();
        });

        Schema::table('material_pressures', function (Blueprint $table) {
            $table->unique(['material_id', 'vector_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_pressures');
    }
};
