<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attractor_spawn_rules', function (Blueprint $table) {
            $table->id();
            $table->string('parent_type');
            $table->string('child_type');
            $table->float('probability')->default(0.0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attractor_spawn_rules');
    }
};
