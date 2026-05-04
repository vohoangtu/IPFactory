<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('universe_decision_models', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('universe_id')->index();
            $table->uuid('policy_id')->nullable()->index();
            $table->enum('model_type', ['linear', 'sigmoid', 'polynomial', 'context_aware'])->default('linear');
            $table->jsonb('weight_vector')->nullable();
            $table->jsonb('interaction_matrix')->nullable();
            $table->jsonb('threshold_vector')->nullable();
            $table->jsonb('context_weights')->nullable();
            $table->unsignedInteger('generation')->default(1);
            $table->timestamps();

            $table->foreign('universe_id')->references('id')->on('universes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('universe_decision_models');
    }
};
