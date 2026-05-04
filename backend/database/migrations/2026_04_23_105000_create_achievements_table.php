<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('category', ['discovery', 'survival', 'diplomacy', 'myth', 'epoch', 'celebrity']);
            $table->string('icon')->nullable();
            $table->enum('rarity', ['common', 'uncommon', 'rare', 'epic', 'legendary'])->default('common');
            $table->json('conditions')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('code');
            $table->index('category');
            $table->index('rarity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
