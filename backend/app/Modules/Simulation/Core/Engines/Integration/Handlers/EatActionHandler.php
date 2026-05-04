<?php

namespace App\Modules\Simulation\Core\Engines\Integration\Handlers;

use App\Modules\Geography\Entities\NaturalResource;
use App\Modules\Simulation\Core\Entities\Agent;
use App\Modules\Simulation\Core\State\WorldState;

/**
 * Handle lệnh "eat" (Ăn).
 * AI kiểm tra túi đồ xem có Food không. Có thì lôi ra cắn.
 */
class EatActionHandler implements ActionHandlerInterface
{
    public function getActionName(): string
    {
        return 'eat';
    }

    public function canExecute(Agent $agent, WorldState $world): bool
    {
        // Chỉ chịu ăn nếu đói (hunger > 0.0) -> Tránh lãng phí thức ăn
        if ($agent->hunger <= 0.0) {
            return false;
        }

        // Kiểm tra trong túi có Thức ăn không
        return $agent->inventory->getCategoryTotal(NaturalResource::CATEGORY_FOOD) > 0;
    }

    public function execute(Agent $agent, WorldState $world): bool
    {
        // 1. Phải chắc chắn có đồ ăn
        if (!$this->canExecute($agent, $world)) {
            return false;
        }

        // 2. Mỗi lần ăn tốn khoảng 5 đơn vị Food
        $amountToEat = min(5.0, $agent->inventory->getCategoryTotal(NaturalResource::CATEGORY_FOOD));
        $actualEaten = $agent->inventory->takeItemByCategory(NaturalResource::CATEGORY_FOOD, $amountToEat);

        if ($actualEaten <= 0) {
            return false;
        }

        // 3. Quy đổi Thức ăn -> Chỉ số sinh tồn
        // 5 units Food = Giảm 0.5 Hunger = Phục hồi cực mạnh
        $hungerRelief = $actualEaten * 0.1;
        $agent->hunger = max(0.0, $agent->hunger - $hungerRelief);
        $agent->energy = min(100.0, $agent->energy + ($actualEaten * 5.0));

        // 4. Phản hồi Tâm lý học (Joy / Relief)
        // Ăn ngon giúp xoa dịu Fear, tạo Joy
        $agent->psychology->applyDelta([
            'joy'    => 0.2, // Sung sướng
            'fear'   => -0.3, // Hết sợ chết đói
            'stress' => -0.2,
            'sadness'=> 0.0,
            'anger'  => 0.0,
        ]);

        return true;
    }
}
