<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fitness_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('universe_id')->index();
            $table->uuid('arena_batch_id')->nullable()->index();
            $table->unsignedInteger('tick');
            $table->float('survival_score')->default(0);
            $table->float('stability_score')->default(0);
            $table->float('diversity_score')->default(0);
            $table->float('complexity_penalty')->default(0);
            $table->float('fitness_total')->default(0);
            $table->timestamp('measured_at')->useCurrent();

            $table->index(['universe_id', 'tick']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_snapshots');
    }
};
