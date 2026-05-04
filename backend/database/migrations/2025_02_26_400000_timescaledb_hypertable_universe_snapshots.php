<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Optional: run only when TimescaleDB extension is installed.
 * Converts universe_snapshots to hypertable for efficient time-series queries.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        try {
            DB::statement('CREATE EXTENSION IF NOT EXISTS timescaledb CASCADE');
            DB::statement("SELECT create_hypertable('universe_snapshots', 'tick', if_not_exists => TRUE, migrate_data => TRUE)");
        } catch (\Throwable $e) {
            // TimescaleDB not installed; skip. Run this migration manually when extension is available.
        }
    }

    public function down(): void
    {
        // Reverting hypertable requires manual migration; leave no-op for safety.
    }
};
