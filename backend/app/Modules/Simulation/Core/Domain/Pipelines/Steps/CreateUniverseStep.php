<?php

namespace App\Modules\Simulation\Core\Domain\Pipelines\Steps;

use App\Modules\World\Models\Universe;

// use App\Modules\Simulation\Core\Domain\Pipelines\SpawnStepInterface;
use Illuminate\Support\Facades\Log;

/**
 * Bước khởi tạo Universe record trong database.
 */
class CreateUniverseStep implements SpawnStepInterface
{
    public function execute(array $context): array
    {
        $world = $context['world'];
        $parentUniverseId = $context['parent_universe_id'];
        $startTick = $context['start_tick'];
        $initialState = $context['initial_state'];
        $genome = $context['genome'];

        $branchLabel = $parentUniverseId ? 'Branch' : 'Genesis';
        $name = $world->name . ' - ' . $branchLabel . ' (' . now()->format('H:i:s') . ')';

        $context['universe'] = \App\Modules\World\Models\Universe::create([
            'name' => $name,
            'world_id' => $world->id,
            'multiverse_id' => $world->multiverse_id,
            'parent_universe_id' => $parentUniverseId,
            'forked_at_tick' => $parentUniverseId ? $startTick : null,
            'current_tick' => $startTick,
            'status' => 'active',
            'state_vector' => $initialState,
            'kernel_genome' => $genome,
        ]);

        return $context;
    }
}

