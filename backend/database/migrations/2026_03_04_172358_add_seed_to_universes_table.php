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
        Schema::table('universes', function (Blueprint $table) {
            if (!Schema::hasColumn('universes', 'seed')) {
                $table->unsignedBigInteger('seed')->default(0)->comment('Universe Seed for Deterministic Simulation');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('universes', function (Blueprint $table) {
            if (Schema::hasColumn('universes', 'seed')) {
                $table->dropColumn('seed');
            }
        });
    }
};
