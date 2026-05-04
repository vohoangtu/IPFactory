<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('universe_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('start_tick');
            $table->unsignedBigInteger('end_tick');
            $table->string('title');
            $table->text('summary')->nullable();
            $table->unsignedBigInteger('detected_at_tick')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eras');
    }
};
