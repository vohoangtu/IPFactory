<?php

namespace App\Modules\Simulation\Console\Commands;

use Database\Seeders\MaterialSeeder;
use Illuminate\Console\Command;

class SeedMaterialsCommand extends Command
{
    protected $signature = 'worldos:seed-materials
                            {--origin= : Origin: vietnamese, european, futuristic, or omit for generic (vietnamese+european)}';

    protected $description = 'Seed Material definitions by origin (WorldOS Material System)';

    public function handle(MaterialSeeder $seeder): int
    {
        $origin = $this->option('origin') ?: 'generic';
        $this->info("Seeding materials for origin: {$origin}");
        $seeder->run($origin);
        $this->info('Done.');
        return 0;
    }
}
