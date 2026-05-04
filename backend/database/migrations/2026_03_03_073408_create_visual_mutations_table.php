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
        Schema::create('visual_mutations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visual_branch_id')->constrained('visual_branches')->onDelete('cascade');
            $table->string('type'); // corruption, ascension, material_influence
            $table->integer('severity'); // 0-100
            $table->json('modifiers');
            $table->string('trigger_event')->nullable();
            $table->unsignedBigInteger('tick');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visual_mutations');
    }
};
