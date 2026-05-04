<?php

namespace App\Modules\Simulation\Core\Runtime\RuleVM;

use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Runtime\State\WorldStateMutable;
use App\Modules\Simulation\Events\SimulationEventOccurred;
use Illuminate\Support\Facades\Log;

/**
 * EffectExecutor: Chịu trách nhiệm áp dụng danh sách Effect và Events từ EngineResult.
 * Tách biệt hoàn toàn logic "Application" khỏi Rule VM.
 */
class EffectExecutor
{
    public function execute(int $universeId, int $tick, EngineResult $result, WorldStateMutable $state): void
    {
        // 1. Áp dụng các thay đổi trạng thái (Effects)
        foreach ($result->stateChanges as $effect) {
            if (method_exists($effect, 'apply')) {
                $effect->apply($state);
            }
        }

        // 2. Bắn các sự kiện mô phỏng
        foreach ($result->events as $eventData) {
            if (is_array($eventData) && !empty($eventData['event_name'])) {
                $this->emitEvent($universeId, $tick, $eventData);
            }
        }
    }

    protected function emitEvent(int $universeId, int $tick, array $eventData): void
    {
        $eventName = $eventData['event_name'];
        $payload = array_merge(
            ['source' => 'rule_vm'], 
            $eventData['metadata'] ?? $eventData['payload'] ?? []
        );

        event(new SimulationEventOccurred($universeId, $eventName, $tick, $payload));
    }
}

