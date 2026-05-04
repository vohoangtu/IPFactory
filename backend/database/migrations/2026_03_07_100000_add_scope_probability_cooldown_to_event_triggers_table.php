<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add scope, probability, cooldown_ticks for data-driven event engine.
     */
    public function up(): void
    {
        Schema::table('event_triggers', function (Blueprint $table) {
            $table->string('scope')->default('universe')->after('threshold_rules')
                ->comment('universe, zone, or institution');
            $table->float('probability')->default(0.2)->after('scope')
                ->comment('Fire probability when rules match');
            $table->unsignedInteger('cooldown_ticks')->default(10)->after('probability')
                ->comment('Min ticks between fires of same event_type per universe');
        });
    }

    public function down(): void
    {
        Schema::table('event_triggers', function (Blueprint $table) {
            $table->dropColumn(['scope', 'probability', 'cooldown_ticks']);
        });
    }
};
