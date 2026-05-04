<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_memories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('universe_id')->nullable();
            $table->string('scope')->default('global');
            $table->string('category')->default('fact');
            $table->text('keywords')->nullable();
            $table->longText('content');
            $table->json('embedding')->nullable();
            $table->timestamps();
            $table->index(['universe_id', 'scope']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_memories');
    }
};

