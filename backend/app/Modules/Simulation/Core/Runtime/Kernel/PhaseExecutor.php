<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Core\Runtime\Kernel;

use App\Modules\Simulation\Core\Runtime\State\WorldState;
use Illuminate\Support\Facades\Log;

class PhaseExecutor
{
    // --- 5 PHASES OF REALITY ---
    public const PHASE_ENVIRONMENT = 'environment';
    public const PHASE_LIFE        = 'life';
    public const PHASE_MIND        = 'mind';
    public const PHASE_SOCIAL      = 'social';
    public const PHASE_META        = 'meta';

    public function executePhase(string $phase, array $categories, WorldState $state, int $tick, array &$tickImpacts): void
    {
        // Enforce Strict Layered Isolation (§Phase 4 Architectures)
        // We capture the specific layer context before executing systems
        $context = $this->preparePhaseContext($phase, $state);

        foreach ($categories as $category => $systems) {
            foreach ($systems as $system) {
                $systemClass = get_class($system);
                if ($system instanceof \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter) {
                    // Try to get the underlying engine class if possible
                    $ref = new \ReflectionClass($system);
                    $prop = $ref->getProperty('engine');
                    $prop->setAccessible(true);
                    $systemClass .= " (" . get_class($prop->getValue($system)) . ")";
                }

                Log::debug("WorldKernel: Executing Phase [{$phase}], Category [{$category}], System [{$systemClass}]");

                // systems now only receive the context for their specific phase
                $report = $system->update($context, $tick);

                if ($report && $report->hasImpacts()) {
                    $tickImpacts[] = $report;

                    // V81: Apply scalar mutations reported by systems (e.g. Entropy changes)
                    $hasMutation = false;
                    foreach ($report->links as $link) {
                        if (isset($link->metadata['mutation'])) {
                            foreach ($link->metadata['mutation'] as $key => $value) {
                                $state->set($key, $value);
                                $hasMutation = true;
                            }
                        }
                    }

                    // Re-prepare context if state was mutated so next systems see the changes
                    if ($hasMutation) {
                        $context = $this->preparePhaseContext($phase, $state);
                    }
                }
            }
        }

        $this->finalizePhase($phase, $state);
    }

    public function preparePhaseContext(string $phase, WorldState $state): ?array
    {
        // Use the Multi-Layer Mapping from WorldState to provide clean context to engines
        return match ($phase) {
            self::PHASE_ENVIRONMENT => $state->getPhysicalLayer(),
            self::PHASE_LIFE        => $state->getLifeLayer(),
            self::PHASE_SOCIAL      => $state->getSocialLayer(),
            self::PHASE_MIND        => $state->getNarrativeLayer(),
            self::PHASE_META        => $state->getMythicLayer(),
            default => null
        };
    }

    public function finalizePhase(string $phase, WorldState $state): void
    {
        // Optional: Perform cross-layer leakage or stabilization logic
    }
}
