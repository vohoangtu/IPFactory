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
        Schema::table('universe_bridges', function (Blueprint $table) {
            $table->float('convergence_score')->default(0.0)->after('is_active');
            $table->unsignedBigInteger('last_synced_tick')->nullable()->after('convergence_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('universe_bridges', function (Blueprint $table) {
            $table->dropColumn(['convergence_score', 'last_synced_tick']);
        });
    }
};

