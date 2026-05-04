<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * threshold_rules: JSON array of {key, op, value} to detect when this event is active.
     * e.g. [{"key": "entropy", "op": ">=", "value": 0.7}, {"key": "stability_index", "op": "<=", "value": 0.4}]
     */
    public function up(): void
    {
        Schema::table('event_triggers', function (Blueprint $table) {
            $table->json('threshold_rules')->nullable()->after('prompt_fragment')->comment('Rules to auto-detect event from state_vector metrics (key, op, value)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_triggers', function (Blueprint $table) {
            $table->dropColumn('threshold_rules');
        });
    }
};
