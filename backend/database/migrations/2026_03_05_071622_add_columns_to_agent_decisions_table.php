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
        Schema::table('agent_decisions', function (Blueprint $table) {
            if (!Schema::hasColumn('agent_decisions', 'impact')) {
                $table->json('impact')->nullable();
            }
            if (!Schema::hasColumn('agent_decisions', 'reasoning')) {
                $table->text('reasoning')->nullable();
            }
            if (!Schema::hasColumn('agent_decisions', 'confidence')) {
                $table->float('confidence')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_decisions', function (Blueprint $table) {
            $table->dropColumn(['impact', 'reasoning', 'confidence']);
        });
    }
};
