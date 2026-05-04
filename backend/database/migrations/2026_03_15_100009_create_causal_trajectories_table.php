<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('causal_trajectories')) {
            return;
        }

        try {
            Schema::create('causal_trajectories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('universe_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('created_tick');
                $table->unsignedBigInteger('prediction_tick');
                $table->text('text');
                $table->float('confidence')->default(0.5)->unsigned();
                $table->boolean('fulfilled')->default(false);
                $table->json('source_snapshot_metrics')->nullable();
                $table->timestamps();
            });
        } catch (\Illuminate\Database\QueryException $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'already exists') || str_contains($msg, '42P07')) {
                return;
            }
            throw $e;
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('causal_trajectories') && Schema::hasColumn('causal_trajectories', 'prediction_tick')) {
            Schema::dropIfExists('causal_trajectories');
        }
    }
};
