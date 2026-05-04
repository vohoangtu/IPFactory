<?php

namespace App\Modules\Simulation\Services\Meta;

use App\Modules\World\Models\World;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Actions\WorldAxiomAction;
use Illuminate\Support\Facades\Log;

class WorldRegulatorEngine
{
    public function __construct(
        protected WorldAxiomAction $axiomAction
    ) {}

    public function process(World $world): void
    {
        if (!$world->is_autonomic) return;

        $activeUniverses = $world->universes->where('status', 'active');
        if ($activeUniverses->isEmpty()) return;

        $avgEntropy = $activeUniverses->avg(fn($u) => (float)(($u->state_vector ?? [])['entropy'] ?? 0.5));
        $avgTech = $activeUniverses->avg(fn($u) => (float)(($u->state_vector ?? [])['innovation'] ?? 0.1));
        $avgStability = $activeUniverses->avg(fn($u) => (float)(($u->state_vector ?? [])['stability_index'] ?? 0.5));
        $avgPop = $activeUniverses->avg(fn($u) => (float)(($u->state_vector ?? [])['population'] ?? 100));

        $currentAxioms = $world->axiom ?? [];
        $newAxioms = [];
        $paradoxScore = 0.0;

        // Self-Regulation Logic
        if ($avgEntropy > 0.8 && ($currentAxioms['entropy_rate'] ?? 1.0) > 0.5) {
            $newAxioms['entropy_rate'] = max(0.4, ($currentAxioms['entropy_rate'] ?? 1.0) * 0.9);
        }

        if ($avgTech < 0.2 && ($currentAxioms['tech_ceiling'] ?? 1.0) < 1.2) {
            $newAxioms['tech_ceiling'] = min(1.3, ($currentAxioms['tech_ceiling'] ?? 1.0) * 1.1);
        }

        if ($avgPop < 50 && ($currentAxioms['growth_multiplier'] ?? 1.0) < 2.0) {
            $newAxioms['growth_multiplier'] = ($currentAxioms['growth_multiplier'] ?? 1.0) * 1.5;
        }

        if ($avgStability < 0.3 && ($currentAxioms['order_bias'] ?? 0.0) < 0.5) {
            $newAxioms['order_bias'] = ($currentAxioms['order_bias'] ?? 0.0) + 0.1;
        }

        if (!empty($newAxioms)) {
            foreach ($newAxioms as $k => $v) {
                $paradoxScore += abs($v - ($currentAxioms[$k] ?? 1.0));
            }

            $this->axiomAction->execute($world, $newAxioms);
            $this->logRegulationEvent($activeUniverses->first(), $paradoxScore, $activeUniverses);
        }
    }

    protected function logRegulationEvent(Universe $u, float $paradox, $universes): void
    {
        $flavor = 'Lồng quay của thực tại xoay chuyển. Thiên Đạo vừa điều chỉnh lại các hằng số hằng hữu.';
        
        if ($paradox > 0.3) {
            $flavor = 'THIÊN ĐẠO NGHỊCH LÝ: Cưỡng ép thay đổi các hằng số quá mức đã gây ra phản chấn. Entropy bùng nổ!';
            foreach ($universes as $uni) {
                $vec = $uni->state_vector ?? [];
                $vec['entropy'] = min(1.0, ($vec['entropy'] ?? 0.0) + 0.15);
                $vec['trauma'] = ($vec['trauma'] ?? 0.0) + 0.2;
                $uni->update(['state_vector' => $vec]);
            }
        }

        \App\Modules\Narrative\Models\Chronicle::create([
            'universe_id' => $u->id,
            'from_tick' => (int)$u->current_tick,
            'to_tick' => (int)$u->current_tick,
            'type' => 'myth',
            'raw_payload' => [
            'action' => 'legacy_event',
            'description' => $flavor
        ]
        ]);
    }
}


