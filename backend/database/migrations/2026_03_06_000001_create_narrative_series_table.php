<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('narrative_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('universe_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('genre_key')->default('wuxia');
            $table->unsignedInteger('current_book_index')->default(1);
            $table->unsignedInteger('total_chapters_generated')->default(0);
            $table->string('status')->default('draft'); // draft, active, completed
            $table->json('config')->nullable(); // pipeline settings
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('narrative_series');
    }
};
