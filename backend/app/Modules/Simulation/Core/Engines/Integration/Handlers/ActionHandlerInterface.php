<?php

namespace App\Modules\Simulation\Core\Engines\Integration\Handlers;

use App\Modules\Simulation\Core\Entities\Agent;
use App\Modules\Simulation\Core\State\WorldState;

interface ActionHandlerInterface
{
    /**
     * Tên lệnh GOAP (ví dụ: 'forage', 'eat', 'wander')
     */
    public function getActionName(): string;

    /**
     * Điều kiện tiên quyết.
     * True -> Thực thi được, False -> Cản trở, bỏ dở sequence GOAP.
     */
    public function canExecute(Agent $agent, WorldState $world): bool;

    /**
     * Logic thực sự làm thay đổi Agent và WorldState.
     * Trả về True nếu thành công trót lọt.
     */
    public function execute(Agent $agent, WorldState $world): bool;
}
