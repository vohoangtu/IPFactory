<?php
/**
 * Project Eons: Physics 2.0 - Universal Axioms
 */

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
            if (!Schema::hasColumn('universes', 'axioms')) {
                $table->jsonb('axioms')->nullable()->comment('Physical constants and fundamental laws of this universe');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('universes', function (Blueprint $table) {
            if (Schema::hasColumn('universes', 'axioms')) {
                $table->dropColumn('axioms');
            }
        });
    }
};
