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
        Schema::create('epochs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('theme')->nullable(); // chaos, order, light, void, etc.
            $table->text('description')->nullable();
            $table->bigInteger('start_tick')->default(0);
            $table->bigInteger('end_tick')->nullable();
            $table->json('axiom_modifiers')->nullable(); // Temporal rule changes
            $table->string('status')->default('active'); // active, archived, past
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('epochs');
    }
};
