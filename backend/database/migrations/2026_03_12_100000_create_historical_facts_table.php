<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historical_facts', function (Blueprint $table) {
            $table->id();
            $table->uuid('world_event_id')->nullable()->index();
            $table->unsignedBigInteger('universe_id')->index();
            $table->unsignedBigInteger('tick')->index();
            $table->unsignedInteger('year')->nullable();
            $table->unsignedBigInteger('zone_id')->nullable()->index();
            $table->unsignedBigInteger('civilization_id')->nullable()->index();
            $table->string('category', 64)->index();
            $table->json('actors')->nullable();
            $table->json('institutions')->nullable();
            $table->json('metrics_before')->nullable();
            $table->json('metrics_after')->nullable();
            $table->json('facts')->nullable();
            $table->timestamps();

            $table->foreign('universe_id')->references('id')->on('universes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historical_facts');
    }
};
