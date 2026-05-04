<?php

namespace App\Modules\Intelligence\Actions;

use App\Modules\World\Models\Universe;
use App\Modules\Institutions\Models\SocialContract;
use App\Modules\Institutions\Models\InstitutionalEntity;
use App\Modules\Narrative\Models\Chronicle;

class SocialContractEvolutionAction
implements \App\Contracts\ActionInterface {
    /**
     * Check and evolve mature social contracts into institutional entities.
     */
    public function execute(Universe $universe, int $tick): void
    {
        $contracts = SocialContract::where('universe_id', $universe->id)
            ->whereNull('institutional_entity_id')
            ->where('expires_at_tick', '>', $tick)
            ->get();

        foreach ($contracts as $contract) {
            // Maturity condition: 3+ participants and existed for at least 30 ticks
            $age = $tick - $contract->created_at_tick; // We need to store created_at_tick
            $participantCount = count($contract->participants ?? []);

            if ($participantCount >= 3) {
                $this->evolve($contract, $universe, $tick);
            }
        }
    }

    protected function evolve(SocialContract $contract, Universe $universe, int $tick): void
    {
        $typeName = match($contract->type) {
            'mutual_defense' => 'Alliance',
            'resource_sharing' => 'Guild',
            'peace_treaty' => 'Coalition',
            default => 'Order',
        };

        $entity = InstitutionalEntity::create([
            'universe_id' => $universe->id,
            'name' => "Emergent {$typeName} #" . rand(100, 999),
            'entity_type' => strtolower($typeName),
            'ideology_vector' => $this->deriveIdeology($contract),
            'org_capacity' => 12.0, // Bonus for being emergent from contract
            'influence_map' => [], // Will be populated by engine
            'spawned_at_tick' => $tick,
        ]);

        $contract->update(['institutional_entity_id' => $entity->id]);

        Chronicle::create([
            'universe_id' => $universe->id,
            'from_tick' => $tick,
            'to_tick' => $tick,
            'type' => 'institutional_emergence',
            'raw_payload' => [
                'action' => 'legacy_event',
                'description' => "TIẾN HÓA XÃ HỘI: Giao ước của một nhóm tác nhân đã kết tinh thành thực thể '{$entity->name}', đánh dấu bước chuyển mình sang giai đoạn tổ chức phức tạp."
            ],
        ]);
    }

    protected function deriveIdeology(SocialContract $contract): array
    {
        // Simple mapping based on type
        return match($contract->type) {
            'mutual_defense' => [
                'tradition' => 0.7, 'innovation' => 0.3, 'trust' => 0.9,
                'violence' => 0.6, 'respect' => 0.8, 'myth' => 0.4
            ],
            'resource_sharing' => [
                'tradition' => 0.4, 'innovation' => 0.8, 'trust' => 0.7,
                'violence' => 0.2, 'respect' => 0.5, 'myth' => 0.3
            ],
            default => [
                'tradition' => 0.5, 'innovation' => 0.5, 'trust' => 0.5,
                'violence' => 0.5, 'respect' => 0.5, 'myth' => 0.5
            ],
        };
    }
}

