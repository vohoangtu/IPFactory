<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('serial_chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('series_id')->constrained('narrative_series')->cascadeOnDelete();
            $table->foreignId('chronicle_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('book_index')->default(1);
            $table->unsignedInteger('chapter_index')->default(1);
            $table->string('title')->nullable();
            $table->longText('content');
            $table->unsignedBigInteger('tick_start')->default(0);
            $table->unsignedBigInteger('tick_end')->nullable();
            $table->boolean('needs_review')->default(true);
            $table->timestamp('canonized_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('serial_chapters');
    }
};
