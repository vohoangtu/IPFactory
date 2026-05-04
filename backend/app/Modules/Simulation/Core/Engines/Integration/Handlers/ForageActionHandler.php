<?php

namespace App\Modules\Simulation\Core\Engines\Integration\Handlers;

use App\Modules\Economics\Services\HarvestingService;
use App\Modules\Geography\Entities\NaturalResource;
use App\Modules\Simulation\Core\Engines\Environment\GeographyEngine;
use App\Modules\Simulation\Core\Entities\Agent;
use App\Modules\Simulation\Core\State\WorldState;

/**
 * Handle lệnh "forage" (Đi hái cỏ, nhặt quả, đào củ).
 * Connects Geography (Tile Resource) -> Economics (Harvest -> Inventory).
 */
class ForageActionHandler implements ActionHandlerInterface
{
    public function __construct(
        private readonly HarvestingService $harvestingService
    ) {}

    public function getActionName(): string
    {
        return 'forage';
    }

    public function canExecute(Agent $agent, WorldState $world): bool
    {
        // Phải có sức (chống chỉ định mệt mỏi)
        if ($agent->energy < 15.0) {
            return false;
        }

        // Túi không được đầy
        if (!$agent->inventory->hasCapacityFor(0.5)) {
            return false;
        }

        // Ô đang đứng phải có FOOD
        $coord = "{$agent->x},{$agent->y}";
        $universeId = (int) $world->get('universe_id', 1);
        $mapState = &GeographyEngine::getPersistentState($universeId);

        if ($mapState === null) {
            return false;
        }

        $resources = $mapState['resources'][$coord] ?? [];
        foreach ($resources as $resource) {
            if ($resource->category === NaturalResource::CATEGORY_FOOD && $resource->currentAmount > 0) {
                return true;
            }
        }

        return false;
    }

    public function execute(Agent $agent, WorldState $world): bool
    {
        if (!$this->canExecute($agent, $world)) {
            return false; // Hủy kèo
        }

        $coord = "{$agent->x},{$agent->y}";
        $universeId = (int) $world->get('universe_id', 1);
        $mapState = &GeographyEngine::getPersistentState($universeId);

        // Lấy con trỏ đến mỏ quặng / bãi trái cây
        $resources = &$mapState['resources'][$coord];
        $harvestedAnything = false;

        foreach ($resources as $id => $resource) {
            if ($resource->category === NaturalResource::CATEGORY_FOOD && $resource->currentAmount > 0) {
                // Tốn 15 Energy để hái 10 món đồ (Tool 1.0 tay không)
                $agent->consumeEnergy(15.0);
                
                $actual = $this->harvestingService->harvestResource($resource, $agent->inventory, 10.0, 1.0);
                
                if ($actual > 0) {
                    $harvestedAnything = true;
                    // Mồ hôi nhỏ xuống, Stress tăng nhẹ vì phải lao động cực nhọc
                    $agent->psychology->applyDelta([
                        'stress' => 0.05,
                        'anger' => 0.0
                    ]);
                    break; // Chỉ hái 1 resource đầu tiên thấy, tránh hack farm 1 lượt vô hạn
                }
            }
        }

        return $harvestedAnything;
    }
}
