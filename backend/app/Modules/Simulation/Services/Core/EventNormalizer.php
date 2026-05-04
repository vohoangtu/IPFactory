<?php

namespace App\Modules\Simulation\Services\Core;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Core\Contracts\WorldEventBusInterface;
use App\Modules\Simulation\Core\Events\WorldEvent;
use App\Modules\Simulation\Core\Events\WorldEventType;

/**
 * Event Normalizer: chuyển trạng thái mô phỏng/raw data thành WorldEvent chuẩn cho Event Bus.
 *
 * Phase 1 (MVP): phát sinh một tick-level WorldEvent tóm tắt entropy/stability/metrics và quyết định AEE.
 * Các nguồn event chi tiết (BranchEvent, ActorEvent, engine chuyên biệt) sẽ được nối vào các phương thức khác ở Phase 2+.
 */
class EventNormalizer
{
    public function __construct(
        protected WorldEventBusInterface $eventBus,
    ) {
    }

    /**
     * Build (do not publish) a tick-summary WorldEvent for Narrative v2: Fact is recorded first, then event published.
     */
    public function buildTickSummaryEvent(Universe $universe, UniverseSnapshot $snapshot, array $decisionData = [], array $scars = []): ?WorldEvent
    {
        $tick = (int) ($snapshot->tick ?? 0);
        if ($tick < 0) {
            return null;
        }

        $metrics = (array) ($snapshot->metrics ?? []);

        $payload = [
            'entropy' => (float) ($snapshot->entropy ?? 0.0),
            'stability_index' => (float) ($snapshot->stability_index ?? 0.0),
            'metrics' => $metrics,
            'decision' => [
                'action' => $decisionData['action'] ?? null,
                'meta' => $decisionData['meta'] ?? [],
            ],
            'scars' => $scars,
        ];

        return WorldEvent::create(
            WorldEventType::PRESSURE_UPDATE,
            (int) $universe->id,
            $tick,
            null,
            [],
            0.0,
            [],
            $payload
        );
    }

    /**
     * Emit a high-level tick summary event (build + publish). Use buildTickSummaryEvent + record + publish for Narrative v2.
     */
    public function emitTickSummaryEvent(Universe $universe, UniverseSnapshot $snapshot, array $decisionData = []): void
    {
        $event = $this->buildTickSummaryEvent($universe, $snapshot, $decisionData);
        if ($event !== null) {
            $this->eventBus->publish($event);
        }
    }

    /**
     * Map a single AgentScar from gRPC response to a WorldEvent.
     */
    public function normalizeScarToEvent(Universe $universe, array $scar): WorldEvent
    {
        $payload = array_merge($scar['raw_payload'] ?? [], [
            'caused_by_id' => $scar['caused_by_id'] ?? 0,
            'metadata' => $scar['metadata'] ?? [],
            'description' => $scar['description'] ?? '',
        ]);

        return WorldEvent::create(
            type: $scar['category'] ?? 'AGENT_SCAR',
            universeId: (int) $universe->id,
            tick: (int) $scar['tick'],
            location: null,
            actors: [$scar['actor_id']],
            impactScore: 0.5, // Default, can be refined based on delta
            causes: $scar['caused_by_id'] ? [$scar['caused_by_id']] : [],
            payload: $payload,
            parentId: $scar['caused_by_id'] ?: null
        );
    }
}



