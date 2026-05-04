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
            if (!Schema::hasColumn('universes', 'structural_coherence')) {
                $table->float('structural_coherence')->default(0.0);
            }
            if (!Schema::hasColumn('universes', 'entropy')) {
                $table->float('entropy')->default(0.0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('universes', function (Blueprint $table) {
            $table->dropColumn(['structural_coherence', 'entropy']);
        });
    }
};
