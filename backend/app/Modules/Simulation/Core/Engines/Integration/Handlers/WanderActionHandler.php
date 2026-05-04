<?php

namespace App\Modules\Simulation\Core\Engines\Integration\Handlers;

use App\Modules\Simulation\Core\Entities\Agent;
use App\Modules\Simulation\Core\State\WorldState;

/**
 * Handle lệnh "wander" (Đi lang thang vô định).
 * Tốn tí sức và di chuyển sang ô lân cận.
 */
class WanderActionHandler implements ActionHandlerInterface
{
    public function getActionName(): string
    {
        return 'wander';
    }

    public function canExecute(Agent $agent, WorldState $world): bool
    {
        // Không thể di chuyển nếu mệt lả (energy < 5)
        return $agent->energy >= 5.0;
    }

    public function execute(Agent $agent, WorldState $world): bool
    {
        if (!$this->canExecute($agent, $world)) {
            return false;
        }

        // Tốn sức chạy loanh quanh
        $agent->consumeEnergy(5.0);

        // Di chuyển ngẫu nhiên
        $dx = rand(-1, 1);
        $dy = rand(-1, 1);
        
        $agent->x += $dx;
        $agent->y += $dy;

        // Giảm stress nhẹ nhờ đi dạo ngắm cảnh ngoài trời
        $agent->psychology->applyDelta([
             'stress' => -0.05,
             'joy' => 0.05,
             'fear' => 0.0,
             'anger' => 0.0,
             'sadness' => 0.0
        ]);

        return true;
    }
}
