<?php

namespace App\Modules\Narrative\Actions;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\BranchEvent;
use Illuminate\Support\Facades\Log;

class DeityEdictAction
implements \App\Contracts\ActionInterface {
    /**
     * Thực thi các sắc lệnh (Edicts) được ban hành từ chính các Agent cấp độ Deity (§53.2).
     */
    public function execute(Universe $universe, array $agentData, string $edictType): void
    {
        Log::info("DEITY EDICT: agent '{$agentData['name']}' has issued a '{$edictType}' in Universe [{$universe->id}]");

        $description = match($edictType) {
            'utopia_protocol' => "Sắc lệnh Thiên đường: Giảm toàn bộ gánh nặng tâm lý xã hội.",
            'singularity_call' => "Tiếng gọi Singularity: Thúc đẩy tiến trình công nghệ vượt bậc.",
            'wrath_of_god' => "Cơn giận của Thần linh: Phá hủy các định chế tham nhũng.",
            default => "Thiên lệnh không xác định."
        };

        BranchEvent::create([
            'universe_id' => $universe->id,
            'from_tick' => $universe->current_tick,
            'event_type' => 'deity_edict',
            'description' => "THIÊN LỆNH: {$description}",
            'payload' => [
                'agent_id' => $agentData['id'] ?? null,
                'agent_name' => $agentData['name'],
                'edict_type' => $edictType
            ]
        ]);

        // Apply immediate macro-effects if needed
        if ($edictType === 'utopia_protocol') {
            $vec = $universe->state_vector;
            $vec['entropy'] = max(0.1, ($vec['entropy'] ?? 0.5) - 0.2);
            $universe->update(['state_vector' => $vec]);
        }
    }
}

