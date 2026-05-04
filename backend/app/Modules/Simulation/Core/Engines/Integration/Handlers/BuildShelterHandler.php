<?php

namespace App\Modules\Simulation\Core\Engines\Integration\Handlers;

use App\Modules\Simulation\Core\Entities\Agent;
use App\Modules\Simulation\Core\Entities\Shelter;
use App\Modules\Simulation\Core\State\WorldState;
use Illuminate\Support\Str;

/**
 * Xây nhà trú ẩn: Tốn 10 Wood + 5 Stone → Tạo Shelter Entity tại vị trí Agent.
 */
class BuildShelterHandler implements ActionHandlerInterface
{
    public function getActionName(): string
    {
        return 'build_shelter';
    }

    public function canExecute(Agent $agent, WorldState $world): bool
    {
        if ($agent->energy < 30.0) return false;

        // Cần 10 wood + 5 stone
        return $agent->inventory->getCategoryTotal('wood') >= 10.0
            && $agent->inventory->getCategoryTotal('stone') >= 5.0;
    }

    public function execute(Agent $agent, WorldState $world): bool
    {
        if (!$this->canExecute($agent, $world)) return false;

        $agent->consumeEnergy(30.0);
        $agent->inventory->takeItemByCategory('wood', 10.0);
        $agent->inventory->takeItemByCategory('stone', 5.0);

        $shelter = new Shelter(
            id: (string) Str::uuid(),
            ownerId: $agent->id,
            x: $agent->x,
            y: $agent->y
        );

        // Lưu vào WorldState
        $shelters = $world->get('shelters', []);
        $shelters[] = $shelter;
        $world->set('shelters', $shelters);

        // Xây nhà xong -> Giảm stress
        $agent->psychology->applyDelta([
            'joy' => 0.2, 'stress' => -0.2, 'fear' => -0.15, 'anger' => 0.0, 'sadness' => 0.0
        ]);

        return true;
    }
}
