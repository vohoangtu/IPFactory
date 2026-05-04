<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 5: Institution extend — founder, idea, type, status, members, zone.
     */
    public function up(): void
    {
        Schema::table('institutional_entities', function (Blueprint $table) {
            $table->foreignId('founder_actor_id')->nullable()->after('universe_id')->constrained('actors')->nullOnDelete();
            $table->foreignId('idea_id')->nullable()->after('founder_actor_id')->constrained('ideas')->nullOnDelete();
            $table->string('institution_type', 64)->nullable()->after('entity_type');
            $table->string('status', 32)->nullable()->after('legitimacy'); // emerging, growing, dominant, declining, collapsed
            $table->unsignedInteger('members')->nullable()->after('status');
            $table->unsignedBigInteger('zone_id')->nullable()->after('influence_map');
        });
    }

    public function down(): void
    {
        Schema::table('institutional_entities', function (Blueprint $table) {
            $table->dropForeign(['founder_actor_id']);
            $table->dropForeign(['idea_id']);
            $table->dropColumn(['institution_type', 'status', 'members', 'zone_id']);
        });
    }
};
