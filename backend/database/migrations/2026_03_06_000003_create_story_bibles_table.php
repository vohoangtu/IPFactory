<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('story_bibles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('series_id')->constrained('narrative_series')->cascadeOnDelete();
            $table->json('characters')->nullable(); // [{name, archetype, description, first_appearance_tick}]
            $table->json('locations')->nullable();  // [{name, description, first_appearance_tick}]
            $table->json('lore')->nullable();        // [{key, description}]
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('story_bibles');
    }
};
