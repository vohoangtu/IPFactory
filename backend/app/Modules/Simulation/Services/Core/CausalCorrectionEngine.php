<?php

namespace App\Modules\Simulation\Services\Core;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Institutions\Models\SupremeEntity;
use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use Illuminate\Support\Facades\Log;
use function resource_path;
use function file_get_contents;
use function abs;
use function max;

class CausalCorrectionEngine
{
    public function __construct(
        protected RuleVmService $ruleVm
    ) {}

    /**
     * Phân tích và thực thi tiến trình tái cân bằng nhân quả để duy trì tính toàn vẹn của vũ trụ.
     */
    public function process(Universe $universe, UniverseSnapshot $snapshot): void
    {
        // Bridge to manifold
    }

    public function runWithState(\App\Modules\Simulation\Core\Runtime\State\WorldState $state, int $tick): void
    {
        $entities = $state->getSupremeEntities();
        if (empty($entities)) return;

        $dslFile = 'simulation/integrity';
        
        // Evaluate integrity rules for the current world state
        $this->ruleVm->evaluateAndApplyWithDsl($state, $dslFile, $tick);
    }

    protected function triggerCorrection(SupremeEntity $entity, Universe $universe, int $tick, array $metadata): void
    {
        $correctionMagnitude = (float) ($metadata['magnitude'] ?? 0);
        
        $entity->update([
            'karma' => (float) ($metadata['new_karma'] ?? ($entity->karma * 0.1)),
            'power_level' => max(1, $entity->power_level - ($correctionMagnitude * 0.1))
        ]);

        $narrative = $metadata['description'] ?? "TÁI CÂN BẰNG TÍNH TOÀN VẸN: Áp lực nhân quả đã được giải tỏa.";

        Chronicle::create([
            'universe_id' => $universe->id,
            'from_tick' => $tick,
            'to_tick' => $tick,
            'type' => 'causal_correction',
            'raw_payload' => [
                'action' => 'legacy_event',
                'description' => $narrative
            ],
            'perceived_archive_snapshot' => [
                'rebalanced_entity' => $entity->name,
                'correction_magnitude' => $correctionMagnitude
            ]
        ]);

        Log::info("Causal Correction executed for Entity [{$entity->name}] in Universe {$universe->id}");
    }
}





