<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('civilizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('universe_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedBigInteger('origin_tick');
            $table->unsignedBigInteger('collapse_tick')->nullable();
            $table->string('culture_group')->nullable();
            $table->unsignedBigInteger('dominant_religion_id')->nullable(); // FK added when religions table exists
            $table->unsignedBigInteger('capital_zone_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('civilizations');
    }
};
