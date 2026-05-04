<?php

namespace App\Modules\Simulation\Core\Engines\Social;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use Illuminate\Support\Facades\Log;
use App\Modules\Simulation\Core\Engines\Meta\WorldWillEngine;

/**
 * CivilizationFieldTheoryEngine – Implementation of Academic Civilization Field Theory (CFT).
 * 
 * This engine manages 10 core and meta fields:
 *   Core: survival (S), power (P), wealth (W), knowledge (K), meaning (M)
 *   Meta: authority (A), fear (F), order (O), entropy (E), resonance (R)
 *
 * Evolution Equation: F_i(t+1) = α*Signal + β*Interaction + γ*Diffusion + δ*Inertia
 */
class CivilizationFieldTheoryEngine
{
    // Field Constants
    public const S = 'survival';
    public const P = 'power';
    public const W = 'wealth';
    public const K = 'knowledge';
    public const M = 'meaning';
    public const A = 'authority';
    public const F = 'fear';
    public const O = 'order';
    public const E = 'entropy';
    public const R = 'resonance';

    public function __construct(
        protected WorldWillEngine $willEngine,
        protected \App\Modules\Simulation\Core\Services\FieldCouplingService $couplingService
    ) {}

    /**
     * Purification: This method now extracts fields already computed by Rust.
     */
    public function computeFromState(\App\Modules\Simulation\Core\Runtime\State\WorldState $state, int $tick): array
    {
        // Get fields calculated by Rust from the global state vector
        $globalFields = $state->get('global_fields', []);
        
        if (!empty($globalFields)) {
            return $this->mapFromRust($globalFields);
        }

        return $state->getFields() ?: $this->getDefaultFields();
    }

    public function compute(Universe $universe, UniverseSnapshot $snapshot): array
    {
        $stateVector = (array)($snapshot->state_vector ?? []);
        
        // If Rust provided global_fields in the snapshot, use them.
        if (isset($stateVector['global_fields'])) {
            return $this->mapFromRust($stateVector['global_fields']);
        }

        return $stateVector['fields'] ?? $this->getDefaultFields();
    }

    protected function mapFromRust(array $rustFields): array
    {
        return [
            self::S => $rustFields['survival'] ?? 0.5,
            self::P => $rustFields['power'] ?? 0.5,
            self::W => $rustFields['wealth'] ?? 0.5,
            self::K => $rustFields['knowledge'] ?? 0.5,
            self::M => $rustFields['meaning'] ?? 0.5,
            self::A => $rustFields['authority'] ?? 0.5,
            self::F => $rustFields['fear_macro'] ?? 0.5,
            self::O => $rustFields['order_macro'] ?? 0.5,
            self::E => $rustFields['entropy_macro'] ?? 0.5,
            self::R => $rustFields['resonance'] ?? 0.5,
        ];
    }

    // Removed computeInteractions as it's now internal to compute() with dynamic weights.

    protected function getFieldKeys(): array
    {
        return [self::S, self::P, self::W, self::K, self::M, self::A, self::F, self::O, self::E, self::R];
    }

    protected function getDefaultFields(): array
    {
        return array_fill_keys($this->getFieldKeys(), 0.5);
    }

    protected function clamp(float $v): float
    {
        return max(0.0, min(1.0, round($v, 4)));
    }
}


