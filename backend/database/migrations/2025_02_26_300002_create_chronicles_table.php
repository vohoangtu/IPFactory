<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chronicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('universe_id')->constrained('universes')->cascadeOnDelete();
            $table->unsignedBigInteger('from_tick');
            $table->unsignedBigInteger('to_tick')->nullable();
            $table->string('type')->default('chronicle'); // chronicle, myth, report
            $table->text('content');
            $table->json('perceived_archive_snapshot')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chronicles');
    }
};
