<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technologies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->json('requirements')->nullable(); // List of code names
            $table->json('effects')->nullable(); // JSON of modifiers
            $table->timestamps();
        });

        Schema::create('actor_technologies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->constrained()->onDelete('cascade');
            $table->foreignId('technology_id')->constrained()->onDelete('cascade');
            $table->float('level')->default(0); // 0.0 to 1.0 (Mastery)
            $table->timestamps();

            $table->unique(['actor_id', 'technology_id']);
        });

        Schema::create('faction_technologies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faction_id')->constrained()->onDelete('cascade');
            $table->foreignId('technology_id')->constrained()->onDelete('cascade');
            $table->float('unlock_status')->default(0); // 0.0 to 1.0
            $table->timestamps();

            $table->unique(['faction_id', 'technology_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faction_technologies');
        Schema::dropIfExists('actor_technologies');
        Schema::dropIfExists('technologies');
    }
};
