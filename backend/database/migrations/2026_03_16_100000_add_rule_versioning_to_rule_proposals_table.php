<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rule versioning (Doc §30): version label and engine manifest at deploy time for replay/pin.
     */
    public function up(): void
    {
        Schema::table('rule_proposals', function (Blueprint $table) {
            $table->string('version', 32)->nullable()->after('sandbox_result');
            $table->json('engine_manifest_snapshot')->nullable()->after('deployed_at');
        });
    }

    public function down(): void
    {
        Schema::table('rule_proposals', function (Blueprint $table) {
            $table->dropColumn(['version', 'engine_manifest_snapshot']);
        });
    }
};
