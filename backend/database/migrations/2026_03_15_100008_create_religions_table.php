<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('religions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('universe_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('origin_myth_id')->nullable()->constrained('myths')->nullOnDelete();
            $table->unsignedBigInteger('founder_actor_id')->nullable();
            $table->text('doctrine')->nullable();
            $table->float('spread_rate')->default(0.1)->unsigned();
            $table->unsignedInteger('followers')->default(0);
            $table->json('holy_sites')->nullable();
            $table->timestamps();
        });

        Schema::table('civilizations', function (Blueprint $table) {
            $table->foreign('dominant_religion_id')->references('id')->on('religions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('civilizations', function (Blueprint $table) {
            $table->dropForeign(['dominant_religion_id']);
        });
        Schema::dropIfExists('religions');
    }
};
