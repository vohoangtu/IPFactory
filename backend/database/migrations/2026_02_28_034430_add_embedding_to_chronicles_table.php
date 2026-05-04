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
        if (DB::getDriverName() === 'pgsql') {
            try {
                DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
            } catch (\Exception $e) {
                // Ignore if permission denied or already exists
            }
        }

        Schema::table('chronicles', function (Blueprint $table) {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('ALTER TABLE chronicles ADD COLUMN IF NOT EXISTS embedding vector(384)');
            } else {
                // Approximate for non-PG (e.g. SQLite tests)
                if (!Schema::hasColumn('chronicles', 'embedding')) {
                    $table->json('embedding')->nullable();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chronicles', function (Blueprint $table) {
            $table->dropColumn('embedding');
        });
    }
};
