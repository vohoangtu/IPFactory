<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Actor identity & lifecycle (multiverse simulation).
     * lineage_id + parent_actor_id: trace genealogy, civilization history, evolution analysis.
     * birth_tick / death_tick: life span in simulation time.
     * life_stage: birth | childhood | adult | elder | death.
     * trait_scan_status: unknown | estimated | confirmed (for 17-D radar UX).
     * vitality: optional JSON { health, age, fatigue, morale } for famine/war/disease engines.
     */
    public function up(): void
    {
        Schema::table('actors', function (Blueprint $table) {
            $table->string('lineage_id', 64)->nullable()->after('generation')
                ->comment('Lineage/family id for genealogy and culture evolution trace');
            $table->foreignId('parent_actor_id')->nullable()->after('lineage_id')
                ->constrained('actors')->nullOnDelete();
            $table->unsignedBigInteger('birth_tick')->nullable()->after('parent_actor_id');
            $table->unsignedBigInteger('death_tick')->nullable()->after('birth_tick');
            $table->string('life_stage', 32)->nullable()->after('death_tick')
                ->comment('birth | childhood | adult | elder | death');
            $table->string('trait_scan_status', 24)->default('unknown')->after('traits')
                ->comment('unknown | estimated | confirmed');
            $table->json('vitality')->nullable()->after('metrics')
                ->comment('Optional: health, age, fatigue, morale (0-1 or 0-100)');

            $table->index('lineage_id');
            $table->index(['universe_id', 'birth_tick']);
        });
    }

    public function down(): void
    {
        Schema::table('actors', function (Blueprint $table) {
            $table->dropForeign(['parent_actor_id']);
            $table->dropIndex(['lineage_id']);
            $table->dropIndex(['universe_id', 'birth_tick']);
            $table->dropColumn([
                'lineage_id', 'parent_actor_id', 'birth_tick', 'death_tick',
                'life_stage', 'trait_scan_status', 'vitality'
            ]);
        });
    }
};
