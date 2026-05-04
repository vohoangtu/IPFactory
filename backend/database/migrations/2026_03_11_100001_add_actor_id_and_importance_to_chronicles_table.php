<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Phase 1: Narrative gravity — Chronicle links to actor and importance.
     */
    public function up(): void
    {
        Schema::table('chronicles', function (Blueprint $table) {
            $table->foreignId('actor_id')->nullable()->after('universe_id')->constrained('actors')->nullOnDelete();
            $table->float('importance')->nullable()->after('to_tick');
        });
    }

    public function down(): void
    {
        Schema::table('chronicles', function (Blueprint $table) {
            $table->dropForeign(['actor_id']);
            $table->dropColumn('importance');
        });
    }
};
