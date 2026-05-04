<?php

namespace App\Modules\Simulation\Core\Engines\Integration\Handlers;

use App\Modules\Simulation\Core\Entities\Agent;
use App\Modules\Simulation\Core\State\WorldState;

/**
 * AI hung hăng (Anger cao, Trust thấp) tấn công Agent khác để cướp đồ.
 */
class AttackHandler implements ActionHandlerInterface
{
    public function getActionName(): string
    {
        return 'attack';
    }

    public function canExecute(Agent $agent, WorldState $world): bool
    {
        if ($agent->energy < 20.0) return false;

        // Phải đủ giận dữ hoặc tuyệt vọng (đói khát)
        if ($agent->psychology->anger < 0.3 && $agent->hunger < 0.7) return false;

        $neighbors = $this->findNeighbors($agent, $world);
        return count($neighbors) > 0;
    }

    public function execute(Agent $agent, WorldState $world): bool
    {
        $neighbors = $this->findNeighbors($agent, $world);
        if (empty($neighbors)) return false;

        $agent->consumeEnergy(20.0);

        /** @var Agent $victim */
        $victim = $neighbors[0]; // Tấn công đứa đầu tiên

        // Damage dựa trên Anger + Hunger tuyệt vọng
        $damage = 10.0 + ($agent->psychology->anger * 20.0);
        $victim->health -= $damage;

        // Cướp food nếu victim có
        $loot = $victim->inventory->takeItemByCategory('food', 5.0);
        if ($loot > 0) {
            $item = new \App\Modules\Economics\ValueObjects\Item(
                (string) \Illuminate\Support\Str::uuid(), 'food', $loot, 0.5
            );
            $agent->inventory->addItem($item);
        }

        // Tâm lý: Kẻ tấn công giảm đói nhưng tăng stress (tội lỗi)
        $agent->psychology->applyDelta([
            'anger' => -0.1, 'stress' => 0.15, 'fear' => 0.0, 'joy' => 0.0, 'sadness' => 0.0
        ]);

        // Nạn nhân: Sợ hãi, giận dữ
        $victim->psychology->applyDelta([
            'fear' => 0.4, 'anger' => 0.3, 'stress' => 0.3, 'joy' => 0.0, 'sadness' => 0.2
        ]);

        return true;
    }

    private function findNeighbors(Agent $agent, WorldState $world): array
    {
        $allAgents = $world->get('agents', []);
        $neighbors = [];
        foreach ($allAgents as $other) {
            if ($other->id !== $agent->id && $other->x === $agent->x && $other->y === $agent->y && $other->isAlive()) {
                $neighbors[] = $other;
            }
        }
        return $neighbors;
    }
}
