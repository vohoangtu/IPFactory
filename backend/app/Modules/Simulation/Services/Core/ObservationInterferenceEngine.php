<?php

namespace App\Modules\Simulation\Services\Core;

use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Entities\UniverseEntity;
use App\Modules\Simulation\Actions\WavefunctionCollapseAction;
use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use Illuminate\Support\Facades\Log;
use function resource_path;
use function file_exists;
use function file_get_contents;

class ObservationInterferenceEngine
{
    public function __construct(
        protected RuleVmService $ruleVm,
        protected WavefunctionCollapseAction $wavefunctionCollapseAction,
        protected \App\Modules\Simulation\Services\Core\ObserverSpectrumService $spectrumService
    ) {}

    /**
     * Phân tích tương tác của Đệ nhất Quan sát nhân và thực thi sụp đổ hàm sóng.
     */
    public function process(UniverseEntity $universe, int $tick, bool $isBeingObserved): void
    {
        // Legacy bridge
    }

    public function runWithState(\App\Modules\Simulation\Core\Runtime\State\WorldState $state, int $tick): void
    {
        $isObserved = $state->isObserved();
        
        // Phase 59: Advanced Observer Spectrum
        $spectrum = $this->spectrumService->getSpectrum($state);
        $signature = $this->spectrumService->getInterferenceSignature($spectrum);
        
        $state->set('meta.observation_load', $signature['total_load']);
        $state->set('meta.observer_spectrum', $spectrum);

        // Apply interference impact
        if ($isObserved || $signature['total_load'] > 1.0) {
            $currentEntropy = $state->getEntropy();
            $currentStability = $state->getStabilityIndex();

            $state->setEntropy(max(0.0, $currentEntropy + $signature['entropy_mod']));
            $state->setStabilityIndex(min(2.0, $currentStability + $signature['stability_mod']));

            // High total load triggers collapse
            if ($signature['total_load'] > 8.0) {
                $this->wavefunctionCollapseAction->executeWithState($state, $tick);
            }
        }

        // Always evaluate observer DSL
        $this->ruleVm->evaluateAndApplyWithDsl($state, 'simulation/observer', $tick);
        
        Log::debug("ObservationInterferenceEngine: Processed quantum state for Universe {$state->get('universe_id')} at tick {$tick}");
    }
}





