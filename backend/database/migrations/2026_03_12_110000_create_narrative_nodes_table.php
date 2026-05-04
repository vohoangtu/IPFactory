<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('narrative_nodes', function (Blueprint $table) {
            $table->id();
            $table->string('node_type', 32)->index();
            $table->string('ref_type', 64)->nullable()->index();
            $table->unsignedBigInteger('ref_id')->nullable()->index();
            $table->unsignedBigInteger('universe_id')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('universe_id')->references('id')->on('universes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('narrative_nodes');
    }
};
