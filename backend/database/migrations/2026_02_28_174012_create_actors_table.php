<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('actors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('universe_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('archetype');
            $table->json('traits'); // 17D vector
            $table->text('biography')->nullable();
            $table->boolean('is_alive')->default(true);
            $table->integer('generation')->default(1);
            $table->json('metrics')->nullable(); // Influence, contribution, etc.
            $table->timestamps();
            
            $table->index(['universe_id', 'is_alive']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actors');
    }
};
