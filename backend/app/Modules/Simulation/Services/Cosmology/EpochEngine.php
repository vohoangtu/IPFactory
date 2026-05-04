<?php

namespace App\Modules\Simulation\Services\Cosmology;

use App\Modules\Simulation\Entities\UniverseEntity;
use App\Modules\World\Models\Universe as UniverseModel;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\World\Models\Epoch;
use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use App\Modules\Simulation\Actions\TransitionEpochAction;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use Illuminate\Support\Facades\Log;
use function resource_path;
use function file_get_contents;
use function array_intersect_key;
use function array_flip;

class EpochEngine
{
    /**
     * Ngưỡng Tick mặc định để xem xét chuyển giao kỷ nguyên (ví dụ: 10,000 tick).
     */
    protected int $epochThreshold = 10000;

    public function __construct(
        protected RuleVmService $ruleVm,
        protected TransitionEpochAction $transitionAction
    ) {}

    /**
     * Kiểm tra và tính toán sự chuyển giao kỷ nguyên.
     */
    public function process(UniverseEntity $universe, UniverseSnapshot $snapshot): void
    {
        $currentEpoch = Epoch::where('world_id', $universe->worldId)
            ->where('status', 'active')
            ->first();

        // Nếu chưa có kỷ nguyên nào, khởi tạo Kỷ Nguyên Khởi Nguyên
        if (!$currentEpoch) {
            $this->initializeFirstEpoch($universe, $snapshot);
            return;
        }

        $tick = $snapshot->tick;
        $relativeTick = $tick - $currentEpoch->start_tick;

        // Phase 101: Enrich vmState with multi-layer metrics
        $worldState = WorldState::fromArray($universe->stateVector);
        $socialLayer = $worldState->getSocialLayer();
        $physicalLayer = $worldState->getPhysicalLayer();

        $vmState = [
            'relative_tick' => (int) $relativeTick,
            'entropy' => (float) $universe->entropy,
            'stability' => (float) ($universe->stateVector['stability_index'] ?? 1.0),
            'tech_level' => (float) ($universe->stateVector['tech_level'] ?? 0.0),
            'population' => (int) ($universe->stateVector['total_population'] ?? 0),
            'social_order' => (float) ($socialLayer['pressures']['social_order'] ?? 1.0),
            'resource_scarcity' => (float) ($physicalLayer['pressures']['resource_scarcity'] ?? 0.5),
            'innovation' => (float) ($universe->stateVector['innovation'] ?? 0),
        ];

        $dslFile = \resource_path('worldos_rules/simulation/epochs.dsl');
        $dsl = @file_get_contents($dslFile) ?: '';
        $result = $this->ruleVm->evaluateRawState($vmState, $dsl);

        if ($result['state']['should_transition'] ?? false) {
            $this->initiateTransition($universe, $currentEpoch, $snapshot, $result);
        }
    }

    protected function initializeFirstEpoch(UniverseEntity $universe, UniverseSnapshot $snapshot): void
    {
        $epoch = Epoch::create([
            'world_id' => $universe->worldId,
            'name' => 'Kỷ Nguyên Khởi Nguyên',
            'theme' => 'genesis',
            'description' => 'Thời đại đầu tiên của thực tại, nơi các quy luật vật lý bắt đầu hình thành.',
            'start_tick' => 0,
            'status' => 'active',
            'axiom_modifiers' => [
                'innovation_rate' => 1.1,
                'stability_bonus' => 0.05
            ]
        ]);

        Log::info("First Epoch Initialized for World {$universe->worldId}: {$epoch->name}");
    }

    protected function initiateTransition(UniverseEntity $universe, Epoch $currentEpoch, UniverseSnapshot $snapshot, array $dslResult): void
    {
        Log::info("Initiating Epoch Transition for World {$universe->worldId} at tick {$snapshot->tick}");
        
        $metadata = [];
        foreach ($dslResult['outputs'] ?? [] as $out) {
            if (($out['event_name'] ?? '') === 'INITIATE_EPOCH_TRANSITION') {
                $metadata = $out['metadata'] ?? [];
                break;
            }
        }

        if (empty($metadata)) {
            // Rollback theme logic from state if event not found? 
            // In Determine_Next_Epoch_Theme rule, we set metadata.
            $metadata = $dslResult['state'] ?? [];
        }

        $nextTheme = [
            'name' => $metadata['name'] ?? 'Kỷ Nguyên Mới',
            'theme' => $metadata['theme'] ?? 'unknown',
            'description' => $metadata['description'] ?? 'Một chương mới của lịch sử đang bắt đầu.',
            'modifiers' => array_intersect_key($metadata, array_flip(['entropy_rate', 'trauma_multiplier', 'innovation_rate', 'complexity_growth', 'stability_bonus', 'conflict_chance']))
        ];
        
        $this->transitionAction->execute($universe, $currentEpoch, $snapshot->tick, $nextTheme);
    }

    protected function determineNextTheme(UniverseEntity $universe): array
    {
        $entropy = $universe->entropy;
        $order = $universe->stabilityIndex;
        $innovation = $universe->stateVector['innovation'] ?? 0;

        if ($entropy > 0.8) {
            return [
                'name' => 'Kỷ Nguyên Hỗn Loạn (The Age of Chaos)',
                'theme' => 'chaos',
                'description' => 'Thực tại rạn nứt, trật tự sụp đổ dưới sức nặng của sự hỗn mang.',
                'modifiers' => ['entropy_rate' => 1.5, 'trauma_multiplier' => 1.2]
            ];
        }

        if ($innovation > 0.7) {
            return [
                'name' => 'Thời Đại Ánh Sáng (The Age of Enlightenment)',
                'theme' => 'light',
                'description' => 'Trí tuệ thăng hoa, các nền văn minh chạm tay vào những bí mật tối thượng.',
                'modifiers' => ['innovation_rate' => 2.0, 'complexity_growth' => 1.3]
            ];
        }

        return [
            'name' => 'Kỷ Nguyên Trật Tự (The Age of Order)',
            'theme' => 'order',
            'description' => 'Một thời kỳ thái bình và ổn định dưới sự giám sát của các quy luật vĩnh cửu.',
            'modifiers' => ['stability_bonus' => 0.15, 'conflict_chance' => 0.5]
        ];
    }
}





