<?php

namespace App\Modules\Simulation\Console\Commands;

use App\Modules\World\Models\Universe;

use Illuminate\Console\Command;

class WorldosDemoCommand extends Command
{
    protected $signature = 'worldos:demo';

    protected $description = 'Seed default Cosmology (Multiverse, World, Saga, Universe) for WorldOS V6 demo';

    public function handle(): int
    {
        $this->call('db:seed', ['--class' => \Database\Seeders\CosmologySeeder::class]);
        return 0;
    }
}
