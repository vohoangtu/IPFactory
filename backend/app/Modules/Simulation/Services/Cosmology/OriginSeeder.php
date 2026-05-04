<?php

namespace App\Modules\Simulation\Services\Cosmology;

use App\Modules\World\Models\Universe;
use App\Contracts\Simulation\SeederInterface;
use App\Modules\Simulation\Services\Seeders;
use Illuminate\Support\Collection;

class OriginSeeder
{
    /** @var Collection<int, SeederInterface> */
    protected Collection $seeders;

    public function __construct()
    {
        // Đăng ký các Seeder thủ công hoặc qua DI
        $this->seeders = collect([
            new Seeders\VietnameseHeritageSeeder(),
            new Seeders\WesternHeritageSeeder(),
            new Seeders\EasternHeritageSeeder(),
            new Seeders\VoidBornSeeder(),
            new Seeders\SolarSeeder(),
            new Seeders\PrimevalSeeder(),
        ]);
    }

    /**
     * Tiêm DNA di sản vào vũ trụ dựa trên Origin của World.
     */
    public function seed(Universe $universe): void
    {
        $world = $universe->world;
        if (!$world || !$world->origin) return;
        $origin = $world->origin;

        $seeder = $this->seeders->first(fn(SeederInterface $s) => $s->supports($origin));

        if ($seeder) {
            $seeder->seed($universe);
        }
    }
}


