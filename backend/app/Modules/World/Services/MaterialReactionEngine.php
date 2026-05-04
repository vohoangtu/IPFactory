<?php

namespace App\Modules\World\Services;

use App\Modules\World\Models\Material;
use App\Modules\Simulation\Models\MaterialInstance;
use App\Modules\Simulation\Models\MaterialReaction;
use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MaterialReactionEngine
{
    public function __construct(
        private readonly RuleVmService $ruleVm,
        private readonly PressureResolver $pressureResolver
    ) {}

    /**
     * Tận dụng RuleVM để xử lý phản ứng vật chất quy mô lớn.
     */
    public function process(WorldState $state): void
    {
        $tick = (int) $state->get('tick', 0);
        $universeId = (int) $state->getUniverseId();
        $zones = $state->getZones();
        $reactions = MaterialReaction::all();

        // Load only instances for this universe, group by context->zone_id
        $instancesByZone = MaterialInstance::where('universe_id', $universeId)
            ->where('lifecycle', 'active')
            ->with('material')
            ->get()
            ->groupBy(fn ($i) => $i->context['zone_id'] ?? null);

        foreach ($zones as &$zone) {
            $zoneId = $zone['id'];
            $zoneInstances = $instancesByZone->get($zoneId, collect());

            if ($zoneInstances->isEmpty() && $tick % 10 !== 0) {
                continue;
            }

            foreach ($reactions as $reaction) {
                if ($this->shouldTrigger($zone, $zoneInstances, $reaction, $tick)) {
                    $this->applyReaction($zone, $zoneInstances, $reaction, $tick, $universeId);
                }
            }
        }

        $state->setZones($zones);
    }

    /**
     * Kiểm tra điều kiện đầu vào và RuleVM DSL.
     */
    private function shouldTrigger(array $zone, Collection $instances, MaterialReaction $reaction, int $tick): bool
    {
        // 1. Check if all inputs are present in sufficient quantity
        $materialCounts = $instances->groupBy(fn ($i) => $i->material->slug ?? '')->map->count();
        foreach ($reaction->inputs as $slug => $requiredQty) {
            if (($materialCounts[$slug] ?? 0) < $requiredQty) {
                return false;
            }
        }

        // 2. Check Probability (Rate)
        if (mt_rand() / mt_getrandmax() > $reaction->rate) {
            return false;
        }

        // 3. Evaluate RuleVM DSL Condition
        if (!empty($reaction->condition)) {
            $rawState = $this->prepareRawState($zone, $tick);
            $result = $this->ruleVm->evaluateRaw($rawState, $reaction->condition);

            $triggered = false;
            foreach ($result['outputs'] ?? [] as $out) {
                if (($out['event_name'] ?? '') === 'REACTION_TRIGGERED') {
                    $triggered = true;
                    break;
                }
            }
            if (!$triggered) {
                return false;
            }
        }

        return true;
    }

    /**
     * Thực hiện phản ứng: Tiêu thụ đầu vào, sinh đầu ra, cập nhật năng lượng/entropy.
     * Wrapped in a DB transaction to prevent partial state on failure.
     */
    private function applyReaction(array &$zone, Collection $instances, MaterialReaction $reaction, int $tick, int $universeId): void
    {
        Log::info("Material Reaction Triggered", [
            'universe_id' => $universeId,
            'zone_id' => $zone['id'],
            'reaction' => $reaction->slug,
        ]);

        DB::transaction(function () use (&$zone, $instances, $reaction, $tick, $universeId) {
            // 1. Consume Inputs — delete by id to avoid cross-universe collisions
            foreach ($reaction->inputs as $slug => $qty) {
                $toDelete = $instances
                    ->filter(fn ($i) => ($i->material->slug ?? '') === $slug)
                    ->take($qty)
                    ->pluck('id');

                MaterialInstance::whereIn('id', $toDelete)->delete();
            }

            // 2. Produce Outputs
            foreach ($reaction->outputs as $slug => $qty) {
                $outputMaterial = Material::where('slug', $slug)->first();
                if (!$outputMaterial) {
                    Log::warning("MaterialReactionEngine: output material slug '{$slug}' not found, skipping.");
                    continue;
                }
                for ($i = 0; $i < $qty; $i++) {
                    MaterialInstance::create([
                        'universe_id' => $universeId,
                        'material_id' => $outputMaterial->id,
                        'lifecycle' => 'active',
                        'activated_at_tick' => $tick,
                        'context' => [
                            'zone_id' => $zone['id'],
                            'origin' => 'reaction:' . $reaction->slug,
                        ],
                    ]);
                }
            }

            // 3. Update Zone Metrics
            $zone['state']['energy'] = max(0, ($zone['state']['energy'] ?? 0) - $reaction->energy_cost);
            $zone['state']['entropy'] = min(1.0, ($zone['state']['entropy'] ?? 0) + $reaction->entropy_produced);
        });

        // Recalculate stress via injected PressureResolver (outside transaction — read-only)
        $zone['state']['material_stress'] = $this->pressureResolver->resolve($zone, app(WorldState::class));
    }

    private function prepareRawState(array $zone, int $tick): array
    {
        return array_merge($zone['state'] ?? [], [
            'tick' => $tick,
            'zone_id' => $zone['id'],
        ]);
    }
}
