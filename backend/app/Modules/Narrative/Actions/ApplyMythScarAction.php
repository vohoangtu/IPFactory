<?php

namespace App\Modules\Narrative\Actions;

use App\Modules\Narrative\Contracts\MythScarRepositoryInterface;
use App\Modules\Narrative\Entities\MythScarEntity;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;

class ApplyMythScarAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        protected \App\Contracts\GraphProviderInterface $graphProvider,
        protected \App\Contracts\RuleVmInterface $ruleVm,
        protected MythScarRepositoryInterface $mythScarRepository
    ) {}

    /**
     * Tự động sinh một Vết Sẹo (Myth Scar) hoặc Di sản (Heritage) dựa trên Rule VM.
     */
    public function execute(Universe $universe, UniverseSnapshot $savedSnapshot, array $decisionData): void
    {
        $eventType = $decisionData['event_type'] ?? 'GENERIC';
        $intensity = (float) ($decisionData['intensity'] ?? 0.5);
        $causalDebt = (float) ($savedSnapshot->state_vector['causal_integrity_debt'] ?? 0.0);

        $rawState = [
            'event_type' => $eventType,
            'event_intensity' => $intensity,
            'causal_integrity_debt' => $causalDebt,
            'field_knowledge_field' => (float) ($savedSnapshot->state_vector['fields']['knowledge_field'] ?? 0.5),
            'current_scars_count' => $this->mythScarRepository->countUnresolved($universe->id),
        ];

        $dslFile = \resource_path('worldos_rules/legend/chronicles.dsl');
        $dsl = '';
        if (is_file($dslFile) && is_readable($dslFile)) {
            $dsl = file_get_contents($dslFile) ?: '';
        } else {
            \Illuminate\Support\Facades\Log::warning("DSL file not found or unreadable: {$dslFile}");
        }
        $result = $this->ruleVm->evaluateRawState($rawState, $dsl);

        if (!($result['ok'] ?? false)) {
            return;
        }

        foreach ($result['outputs'] ?? [] as $output) {
            if ($output['type'] === 'event' && $output['event_name'] === 'CREATE_WORLD_SCAR') {
                $this->createMythScar($universe, $output['data']);
            }
            // Logic for CREATE_HERITAGE can be added here
        }
    }

    private function createMythScar(Universe $universe, array $data): void
    {
        $scarEntity = MythScarEntity::create([
            'universe_id'      => $universe->id,
            'zone_id'          => 'Global',
            'name'             => ($data['type'] ?? 'Unknown') . " Scar",
            'description'      => "Dấu ấn lịch sử: " . ($data['type'] ?? 'Unknown'),
            'severity'         => (float) ($data['weight'] ?? 0.5),
            'decay_rate'       => 0.005,
            'created_at_tick'  => $universe->current_tick,
        ]);

        $savedEntity = $this->mythScarRepository->save($scarEntity);

        $this->graphProvider->sync($universe->id, [
            'type' => 'MythScar',
            'model' => $savedEntity->toArray()
        ]);
    }
}


