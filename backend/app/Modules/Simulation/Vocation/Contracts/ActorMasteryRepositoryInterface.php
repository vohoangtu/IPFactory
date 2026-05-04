<?php

namespace App\Modules\Simulation\Vocation\Contracts;

use App\Modules\Simulation\Vocation\Entities\ActorMasteryEntity;

interface ActorMasteryRepositoryInterface
{
    public function findByActorAndVocation(string $actorId, string $vocationId): ?ActorMasteryEntity;
    public function getByActor(string $actorId): array;
    public function save(ActorMasteryEntity $entity): void;
}
