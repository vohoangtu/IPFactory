<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legends', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->foreignId('legendary_agent_id')->nullable()->constrained('legendary_agents')->nullOnDelete();
            $table->string('title')->nullable();
            $table->text('story')->nullable();
            $table->float('power_score')->default(0)->unsigned();
            $table->unsignedTinyInteger('legend_level')->default(1); // 1=hero .. 5=godlike
            $table->json('achievement_ids')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legends');
    }
};
