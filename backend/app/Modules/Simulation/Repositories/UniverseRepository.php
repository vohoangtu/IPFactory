<?php

namespace App\Modules\Simulation\Repositories;

use App\Contracts\Repositories\UniverseRepositoryInterface;
use App\Modules\World\Models\Universe;

class UniverseRepository implements UniverseRepositoryInterface
{
    public function find(int $id): ?Universe
    {
        return Universe::find($id);
    }

    public function create(array $data): Universe
    {
        return Universe::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $universe = $this->find($id);
        if (!$universe) {
            return false;
        }

        return $universe->update($data);
    }

    public function fork(Universe $parent, int $tick, array $newParams): Universe
    {
        $vector = array_merge($parent->sim_vector ?? [], $newParams);
        
        return Universe::create([
            'multiverse_id' => $parent->multiverse_id,
            'saga_id' => $parent->saga_id,
            'world_id' => $parent->world_id,
            'parent_universe_id' => $parent->id,
            'started_at_tick' => $tick,
            'sim_vector' => $vector,
            'status' => 'running'
        ]);
    }
}

