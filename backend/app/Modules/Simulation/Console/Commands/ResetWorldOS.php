<?php

namespace App\Modules\Simulation\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetWorldOS extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'worldos:reset {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset WorldOS: Truncate all simulation data to start fresh.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('force') || $this->confirm('This will truncate all simulation tables (Universes, Sagas, Worlds, Materials). Are you sure?')) {
            $this->info("Resetting WorldOS...");

            // Disable foreign key checks
            Schema::disableForeignKeyConstraints();

            // List of tables to truncate
            $tables = [
                'branch_events',
                'chronicles',
                'material_instances',
                'material_mutations',
                'universes',
                'sagas',
                'worlds',
                // 'multiverses', // Keep multiverse root? Or wipe it too? Let's wipe it for a full clean slate.
                // 'materials',   // Materials might be seeded, but let's wipe to avoid duplicates if seed is run again.
            ];

            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->truncate();
                    $this->line(" - Truncated: {$table}");
                }
            }

            // Re-enable foreign key checks
            Schema::enableForeignKeyConstraints();

            // Clear Redis if needed (optional, assuming default cache)
            $this->call('cache:clear');

            $this->info("WorldOS has been reset successfully. Ready for a new Genesis.");
            
            // Optional: Ask to run seed
            if ($this->confirm('Do you want to run the Demo Scenario immediately?', true)) {
                $this->call('worldos:demo-scenario');
            }
        }
    }
}
