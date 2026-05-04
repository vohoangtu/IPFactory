<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('ontology'); // physical, institutional, symbolic, behavioral
            $table->string('lifecycle')->default('dormant'); // dormant, active, obsolete
            $table->json('inputs')->nullable();
            $table->json('outputs')->nullable();
            $table->json('pressure_coefficients')->nullable(); // entropy, order, innovation, growth, trauma...
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
