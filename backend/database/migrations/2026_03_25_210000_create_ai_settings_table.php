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
        Schema::create('ai_settings', function (Blueprint $col) {
            $col->id();
            $col->string('key')->unique(); // e.g., 'zai.api_key', 'features.narrative'
            $col->text('value')->nullable();
            $col->string('group')->default('general'); // 'provider', 'feature', 'system'
            $col->string('description')->nullable();
            $col->boolean('is_secret')->default(false);
            $col->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_settings');
    }
};
