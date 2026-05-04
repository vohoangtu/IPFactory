<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('civilizations_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('civilization_id')->constrained()->cascadeOnDelete();
            $table->text('origin_story')->nullable();
            $table->text('golden_age_story')->nullable();
            $table->text('collapse_story')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('civilizations_history');
    }
};
