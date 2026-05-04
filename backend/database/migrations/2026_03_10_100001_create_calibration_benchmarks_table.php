<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calibration_benchmarks', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->decimal('value', 18, 6);
            $table->string('unit')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calibration_benchmarks');
    }
};
