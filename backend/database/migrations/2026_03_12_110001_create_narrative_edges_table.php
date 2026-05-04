<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('narrative_edges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('from_node_id')->index();
            $table->unsignedBigInteger('to_node_id')->index();
            $table->string('edge_type', 64)->index();
            $table->string('perspective', 32)->nullable()->index();
            $table->float('weight')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('from_node_id')->references('id')->on('narrative_nodes')->onDelete('cascade');
            $table->foreign('to_node_id')->references('id')->on('narrative_nodes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('narrative_edges');
    }
};
