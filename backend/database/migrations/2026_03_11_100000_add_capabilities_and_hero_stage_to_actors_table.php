<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Phase 1: Capability layer + hero lifecycle (hero_stage).
     */
    public function up(): void
    {
        Schema::table('actors', function (Blueprint $table) {
            $table->json('capabilities')->nullable()->after('metrics');
            $table->string('hero_stage', 32)->nullable()->after('life_stage');
        });
    }

    public function down(): void
    {
        Schema::table('actors', function (Blueprint $table) {
            $table->dropColumn(['capabilities', 'hero_stage']);
        });
    }
};
