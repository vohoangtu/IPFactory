<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('world_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('universe_id')->index();
            $table->unsignedInteger('tick')->index();
            $table->string('type', 64)->index();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->foreign('universe_id')->references('id')->on('universes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('world_events');
    }
};
