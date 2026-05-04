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
        Schema::create('social_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('universe_id')->constrained()->onDelete('cascade');
            $table->string('type'); // mutual_defense, resource_sharing, etc.
            $table->json('participants'); // Array of actor IDs
            $table->float('strictness')->default(0.5);
            $table->integer('duration')->nullable();
            $table->integer('created_at_tick')->default(0);
            $table->integer('expires_at_tick')->nullable();
            $table->foreignId('institutional_entity_id')->nullable()->constrained('institutional_entities')->onDelete('set null');
            $table->timestamps();

            $table->index(['universe_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_contracts');
    }
};
