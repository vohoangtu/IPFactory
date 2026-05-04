<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vocation_definitions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('min_tier')->default(0);
            $table->jsonb('tags')->nullable();
            $table->jsonb('motivation_profile'); // 8D model
            $table->jsonb('requirements')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vocation_definitions');
    }
};
