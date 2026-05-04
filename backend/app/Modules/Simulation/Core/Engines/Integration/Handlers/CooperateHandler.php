<?php

namespace App\Modules\Simulation\Core\Engines\Integration\Handlers;

use App\Modules\Simulation\Core\Entities\Agent;
use App\Modules\Simulation\Core\State\WorldState;

/**
 * AI hòa bình (Joy cao, Trust cao) chia sẻ thức ăn cho Agent đang đói trên cùng Tile.
 */
class CooperateHandler implements ActionHandlerInterface
{
    public function getActionName(): string
    {
        return 'cooperate';
    }

    public function canExecute(Agent $agent, WorldState $world): bool
    {
        if ($agent->energy < 5.0) return false;
        // Phải có đồ thừa để chia sẻ
        if ($agent->inventory->getCategoryTotal('food') < 3.0) return false;
        // Phải sẵn sàng hòa bình (Joy > 0.3)
        if ($agent->psychology->joy < 0.2) return false;

        $neighbors = $this->findHungryNeighbors($agent, $world);
        return count($neighbors) > 0;
    }

    public function execute(Agent $agent, WorldState $world): bool
    {
        $hungryNeighbors = $this->findHungryNeighbors($agent, $world);
        if (empty($hungryNeighbors)) return false;

        $agent->consumeEnergy(5.0);

        /** @var Agent $recipient */
        $recipient = $hungryNeighbors[0];

        // Chia sẻ tối đa 3 food
        $shared = $agent->inventory->takeItemByCategory('food', 3.0);
        if ($shared > 0) {
            $item = new \App\Modules\Economics\ValueObjects\Item(
                (string) \Illuminate\Support\Str::uuid(), 'food', $shared, 0.5
            );
            $recipient->inventory->addItem($item);
        }

        // Tâm lý: Cả hai vui vẻ, tăng trust
        $agent->psychology->applyDelta([
            'joy' => 0.15, 'stress' => -0.1, 'fear' => 0.0, 'anger' => 0.0, 'sadness' => -0.05
        ]);
        $recipient->psychology->applyDelta([
            'joy' => 0.2, 'fear' => -0.15, 'stress' => -0.15, 'anger' => -0.1, 'sadness' => -0.1
        ]);

        return true;
    }

    private function findHungryNeighbors(Agent $agent, WorldState $world): array
    {
        $allAgents = $world->get('agents', []);
        $neighbors = [];
        foreach ($allAgents as $other) {
            if ($other->id !== $agent->id && $other->x === $agent->x && $other->y === $agent->y 
                && $other->isAlive() && $other->hunger > 0.5) {
                $neighbors[] = $other;
            }
        }
        return $neighbors;
    }
}
