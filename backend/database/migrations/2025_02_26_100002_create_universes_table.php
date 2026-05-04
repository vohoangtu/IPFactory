<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('universes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('multiverse_id')->nullable()->constrained('multiverses')->nullOnDelete();
            $table->foreignId('parent_universe_id')->nullable()->constrained('universes')->nullOnDelete();
            $table->unsignedBigInteger('current_tick')->default(0);
            $table->string('status')->default('active'); // active, halted, archived
            $table->json('state_vector')->nullable(); // current state for quick read
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('universes');
    }
};
