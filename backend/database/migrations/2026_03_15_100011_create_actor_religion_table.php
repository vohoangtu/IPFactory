<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actor_religion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('religion_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('believed_at_tick')->default(0);
            $table->timestamps();

            $table->unique(['actor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actor_religion');
    }
};
