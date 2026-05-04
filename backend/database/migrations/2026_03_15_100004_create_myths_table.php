<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('myths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('universe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chronicle_id')->nullable()->constrained()->nullOnDelete();
            $table->string('myth_type', 32); // legend, religion, causal_trajectory
            $table->text('story');
            $table->json('source_events')->nullable();
            $table->float('impact')->default(0)->unsigned(); // 0-1 for religion seed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('myths');
    }
};
