<?php

namespace App\Modules\Simulation\Core\Services;

use App\Modules\Simulation\Core\Entities\Agent;

/**
 * Faction & Governance: Quản lý phe phái và bầu cử lãnh đạo.
 * Khi Settlement đủ đông → Tự động bầu Leader dựa trên Reputation.
 */
class FactionService
{
    /**
     * Tạo Faction từ danh sách Agent trong cùng Settlement.
     * 
     * @param Agent[] $members
     * @return array{id: string, leader_id: string, members: string[], culture_bias: string}
     */
    public function createFaction(array $members, string $factionId): array
    {
        if (count($members) < 3) {
            return ['id' => $factionId, 'leader_id' => '', 'members' => [], 'culture_bias' => 'neutral'];
        }

        $leader = $this->electLeader($members);
        $memberIds = array_map(fn(Agent $a) => $a->id, $members);

        // Văn hóa Faction bị ảnh hưởng bởi tính cách Leader
        $cultureBias = $this->determineCultureBias($leader);

        return [
            'id' => $factionId,
            'leader_id' => $leader->id,
            'members' => $memberIds,
            'culture_bias' => $cultureBias,
        ];
    }

    /**
     * Bầu cử Leader: Agent có điểm uy tín cao nhất (dựa trên health + joy - anger).
     * Heuristic đơn giản cho Phase 1.
     */
    public function electLeader(array $agents): Agent
    {
        usort($agents, function (Agent $a, Agent $b) {
            $scoreA = $this->calculateReputation($a);
            $scoreB = $this->calculateReputation($b);
            return $scoreB <=> $scoreA; // Cao nhất lên đầu
        });

        return $agents[0];
    }

    public function calculateReputation(Agent $agent): float
    {
        // Health cao + Joy cao + ít Anger = uy tín
        return ($agent->health / 100.0)
            + $agent->psychology->joy
            - $agent->psychology->anger
            + (1.0 - $agent->psychology->stress) * 0.5;
    }

    /**
     * Leader hung hăng → Faction hiếu chiến.
     * Leader hòa bình → Faction hòa nhã.
     */
    private function determineCultureBias(Agent $leader): string
    {
        if ($leader->psychology->anger > 0.5) return 'aggressive';
        if ($leader->psychology->joy > 0.5) return 'peaceful';
        if ($leader->traits->openness > 0.7) return 'progressive';
        return 'conservative';
    }
}
