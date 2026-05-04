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
        Schema::create('agent_configs', function (Blueprint $table) {
            $table->id();
            $table->string('agent_name');
            $table->string('personality')->default('Objective');
            $table->integer('creativity')->default(50);
            $table->json('themes')->nullable();
            $table->string('model_type')->default('local');
            $table->string('local_endpoint')->nullable();
            $table->string('model_name')->nullable();
            $table->string('api_key')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_configs');
    }
};
