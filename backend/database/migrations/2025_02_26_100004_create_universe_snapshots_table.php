<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('universe_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('universe_id')->constrained('universes')->cascadeOnDelete();
            $table->unsignedBigInteger('tick');
            $table->json('state_vector');
            $table->decimal('entropy', 10, 6)->nullable();
            $table->decimal('stability_index', 10, 6)->nullable();
            $table->json('metrics')->nullable();
            $table->timestamps();
        });

        Schema::table('universe_snapshots', function (Blueprint $table) {
            $table->unique(['universe_id', 'tick']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('universe_snapshots');
    }
};
