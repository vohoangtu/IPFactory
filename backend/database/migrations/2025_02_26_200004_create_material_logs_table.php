<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_instance_id')->constrained('material_instances')->cascadeOnDelete();
            $table->string('event'); // activated, obsolete, pressure_applied...
            $table->unsignedBigInteger('tick')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_logs');
    }
};
