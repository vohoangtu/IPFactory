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
        Schema::create('demiurges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('intention_type'); // order, chaos, void, stasis, sovereignty
            $table->integer('will_power')->default(100);
            $table->boolean('is_active')->default(true);
            $table->json('config')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demiurges');
    }
};
