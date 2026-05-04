<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_provider_models', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->index();
            $table->string('model_name')->unique();
            $table->string('display_name');
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'model_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_provider_models');
    }
};
