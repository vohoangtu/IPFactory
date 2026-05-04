<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('universe_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('universe_id')->constrained()->cascadeOnDelete();
            $table->longText('full_text');
            $table->unsignedBigInteger('from_tick')->nullable();
            $table->unsignedBigInteger('to_tick')->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('universe_histories');
    }
};
