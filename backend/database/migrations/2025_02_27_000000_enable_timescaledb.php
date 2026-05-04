<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip if not using PostgreSQL (e.g. SQLite in tests)
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // TimescaleDB is optional — skip gracefully if not available on this host
        try {
            DB::statement('CREATE EXTENSION IF NOT EXISTS timescaledb CASCADE;');
        } catch (\Exception $e) {
            // TimescaleDB extension not available on this host; skip hypertable conversion.
            return;
        }

        // 2. Convert universe_snapshots to hypertable
        if (!Schema::hasTable('universe_snapshots')) {
            return;
        }

        try {
            $isHypertable = DB::select(
                "SELECT * FROM timescaledb_information.hypertables WHERE hypertable_name = 'universe_snapshots'"
            );

            if (empty($isHypertable)) {
                Schema::table('universe_snapshots', function (Blueprint $table) {
                    $table->dropPrimary();
                });

                DB::statement("SELECT create_hypertable('universe_snapshots', 'tick', chunk_time_interval => 1000, migrate_data => true);");
                DB::statement("ALTER TABLE universe_snapshots SET (timescaledb.compress, timescaledb.compress_segmentby = 'universe_id');");
                DB::statement("SELECT add_compression_policy('universe_snapshots', 5000);");
            }
        } catch (\Exception $e) {
            // TimescaleDB hypertable conversion failed; continuing without it.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Irreversible
    }
};
