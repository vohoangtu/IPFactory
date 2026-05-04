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
        Schema::table('worlds', function (Blueprint $table) {
            $table->string('civilization_era')->nullable()->after('current_genre');
            $table->string('power_system_type')->nullable()->after('civilization_era');
            $table->float('power_system_bootstrap_energy')->default(0)->after('power_system_type');
            $table->unsignedInteger('version')->default(1)->after('power_system_bootstrap_energy');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('worlds', function (Blueprint $table) {
            $table->dropColumn(['civilization_era', 'power_system_type', 'power_system_bootstrap_energy', 'version']);
        });
    }
};
