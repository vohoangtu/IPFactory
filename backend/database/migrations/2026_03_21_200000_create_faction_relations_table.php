<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faction_relations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('from_faction_id');
            $table->unsignedBigInteger('to_faction_id');
            $table->string('type')->default('neutral'); // alliance, neutral, hostile
            $table->float('tension')->default(0.5); // 0.0 (friend) to 1.0 (war)
            $table->timestamps();

            $table->foreign('from_faction_id')->references('id')->on('factions')->onDelete('cascade');
            $table->foreign('to_faction_id')->references('id')->on('factions')->onDelete('cascade');
            
            // Đảm bảo không có quan hệ trùng lặp giữa cặp faction
            $table->unique(['from_faction_id', 'to_faction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faction_relations');
    }
};
