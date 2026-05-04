<?php

namespace App\Modules\Simulation\Contracts;

use App\Modules\Simulation\Entities\TrajectoryEntity;
use Illuminate\Support\Collection;

interface TrajectoryRepositoryInterface
{
    public function save(TrajectoryEntity $trajectory): TrajectoryEntity;
    public function getLatestForUniverse(int $universeId, int $limit = 5): Collection;
}
