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
            if (!Schema::hasColumn('universes', 'last_observed_at')) {
                $table->timestamp('last_observed_at')->nullable();
            }
            if (!Schema::hasColumn('universes', 'observer_bonus')) {
                $table->float('observer_bonus')->default(0.0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('universes', function (Blueprint $table) {
            if (Schema::hasColumn('universes', 'last_observed_at')) {
                $table->dropColumn('last_observed_at');
            }
            if (Schema::hasColumn('universes', 'observer_bonus')) {
                $table->dropColumn('observer_bonus');
            }
        });
    }
};
