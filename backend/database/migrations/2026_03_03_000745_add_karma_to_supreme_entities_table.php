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
        Schema::table('supreme_entities', function (Blueprint $table) {
            $table->float('karma')->default(0); // Karma Debt
            $table->json('karma_metadata')->nullable(); // Behavioral history for Chronicler
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supreme_entities', function (Blueprint $table) {
            //
        });
    }
};
