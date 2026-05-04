<?php

namespace App\Modules\Simulation\Contracts;

use App\Modules\Simulation\Entities\RelicEntity;
use Illuminate\Support\Collection;

interface RelicRepositoryInterface
{
    public function save(RelicEntity $relic): RelicEntity;
    public function findById(int $id): ?RelicEntity;
    public function getForWorld(int $worldId): Collection;
}
