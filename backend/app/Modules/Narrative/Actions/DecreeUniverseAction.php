<?php

namespace App\Modules\Narrative\Actions;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Institutions\Services\WorldEdictEngine;

class DecreeUniverseAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        protected WorldEdictEngine $edictEngine
    ) {}

    public function execute(Universe $universe, string $edictId): array
    {
        $snapshot = UniverseSnapshot::where('universe_id', $universe->id)
            ->orderBy('tick', 'desc')
            ->first();

        if (!$snapshot) {
            return ['ok' => false, 'error' => 'No snapshot found'];
        }

        $metrics = is_string($snapshot->metrics) ? json_decode($snapshot->metrics, true) : ($snapshot->metrics ?? []);
        
        $success = $this->edictEngine->activateEdict($universe, $snapshot->tick, $metrics, $edictId, 'Đấng Sáng Thế');

        if ($success) {
            $snapshot->metrics = $metrics;
            $snapshot->save();
            return ['ok' => true];
        }

        return ['ok' => false, 'error' => 'Edict already active or invalid'];
    }
}

