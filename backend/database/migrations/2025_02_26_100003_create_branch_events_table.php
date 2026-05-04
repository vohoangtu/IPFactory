<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('universe_id')->constrained('universes')->cascadeOnDelete();
            $table->unsignedBigInteger('from_tick');
            $table->string('event_type'); // fork, collapse
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_events');
    }
};
