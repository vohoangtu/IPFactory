<?php

namespace App\Modules\WorldOS\Actions;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\BranchEvent;
use App\Modules\Simulation\Services\Core\ImplicitOrchestratorService;

class ForkUniverseAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        private ImplicitOrchestratorService $orchestrator
    ) {}

    public function handle(Universe $universe, int $tick, ?string $name = null): Universe
    {
        // Ghi lại sự kiện fork
        BranchEvent::create([
            'universe_id' => $universe->id,
            'from_tick' => $tick > 0 ? $tick : (int) $universe->current_tick,
            'event_type' => 'fork',
            'payload' => ['manual' => true, 'custom_name' => $name],
        ]);
        
        // Thực hiện spawn vũ trụ con
        $child = $this->orchestrator->spawnUniverse(
            $universe->world, 
            $universe->id, 
            $universe->saga_id
        );

        // Nếu có tên tùy chỉnh, cập nhật ngay
        if ($name) {
            $child->update(['name' => $name]);
        }

        return $child;
    }
}
