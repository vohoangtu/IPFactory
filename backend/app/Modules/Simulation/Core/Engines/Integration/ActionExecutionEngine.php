<?php

namespace App\Modules\Simulation\Core\Engines\Integration;

use App\Modules\Simulation\Core\Entities\Agent;
use App\Modules\Simulation\Core\Engines\Integration\Handlers\ActionHandlerInterface;
use App\Modules\Simulation\Core\State\WorldState;
use Exception;

class ActionExecutionEngine
{
    /** @var array<string, ActionHandlerInterface> */
    private array $handlers = [];

    public function registerHandler(ActionHandlerInterface $handler): void
    {
        $this->handlers[$handler->getActionName()] = $handler;
    }

    /**
     * Vòng lặp Core cho 1 Tick hành động của 1 mảng Agent
     * Trả về thống kê Logs
     */
    public function tickAgents(array $agents, WorldState $worldState): array
    {
        $logs = [];

        /** @var Agent $agent */
        foreach ($agents as $agent) {
            // 1. Phản ứng Sinh học (Tụt energy, tăng hunger, máu)
            $agent->biologicalTick();

            // AI chết rồi thì không làm gì
            if (!$agent->isAlive()) {
                $logs[] = "{$agent->id} is dead.";
                continue;
            }

            // 2. Lấy hành động đầu tiên trong kế hoạch GOAP
            $actionName = $agent->getNextAction();

            if (!$actionName) {
                // Không có kế hoạch gì (Idle)
                continue;
            }

            if (!isset($this->handlers[$actionName])) {
                $logs[] = "{$agent->id} tried unknown action: $actionName";
                $agent->markActionCompleted(); // Bỏ qua action lỗi
                continue;
            }

            $handler = $this->handlers[$actionName];

            // 3. Xin phép thực thi
            if ($handler->canExecute($agent, $worldState)) {
                $success = $handler->execute($agent, $worldState);
                if ($success) {
                    $logs[] = "{$agent->id} successfully executed $actionName.";
                    $agent->markActionCompleted(); // Xóa thẻ
                } else {
                    $logs[] = "{$agent->id} failed executing $actionName.";
                    // Giữ lại action để thử lại turn sau, hoặc GOAP mới sẽ ghi đè
                }
            } else {
                $logs[] = "{$agent->id} cannot execute $actionName due to unmet conditions.";
                // Cản trở vật lý -> Xóa luôn để GOAP lập kế hoạch mới
                $agent->markActionCompleted();
            }
        }

        return $logs;
    }
}
