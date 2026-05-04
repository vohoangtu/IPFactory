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
        Schema::create('civilization_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedInteger('generation')->default(1);
            $table->uuid('arena_batch_id')->nullable()->index();
            $table->uuid('parent_policy_id')->nullable();
            $table->float('survival_priority')->default(1.0);
            $table->float('stability_priority')->default(0.6);
            $table->float('diversity_priority')->default(0.4);
            $table->float('fitness_score')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('civilization_policies');
    }
};
