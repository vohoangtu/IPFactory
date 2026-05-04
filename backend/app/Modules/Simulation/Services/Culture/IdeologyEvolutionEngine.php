<?php

namespace App\Modules\Simulation\Services\Culture;

use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Institutions\Models\InstitutionalEntity;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Services\Culture\IdeologyConversionService;
use Illuminate\Support\Facades\Log;

/**
 * Ideology Evolution Engine (Phase G): aggregates ideology from institutions
 * into a dominant/emerging ideology for the universe. Optionally stores in state_vector.
 */
class IdeologyEvolutionEngine
{
    private const IDEOLOGY_KEYS = ['tradition', 'innovation', 'trust', 'violence', 'respect', 'myth'];

    public function __construct(
        protected IdeologyConversionService $conversionService
    ) {}

    /**
     * Compute dominant ideology for the universe from active institutions.
     *
     * @return array{dominant: array<string, float>, institution_count: int, previous_dominant: array|null}
     */
    public function getDominantIdeology(Universe $universe): array
    {
        $institutions = InstitutionalEntity::where('universe_id', $universe->id)
            ->whereNull('collapsed_at_tick')
            ->get();

        $count = $institutions->count();
        if ($count === 0) {
            $empty = array_fill_keys(self::IDEOLOGY_KEYS, 0.5);
            return [
                'dominant' => $empty,
                'institution_count' => 0,
                'previous_dominant' => $this->getStoredIdeology($universe),
            ];
        }

        $agg = array_fill_keys(self::IDEOLOGY_KEYS, 0.0);
        foreach ($institutions as $inst) {
            $vec = (array) ($inst->ideology_vector ?? []);
            foreach (self::IDEOLOGY_KEYS as $k) {
                $agg[$k] += (float) ($vec[$k] ?? 0.5);
            }
        }
        foreach (self::IDEOLOGY_KEYS as $k) {
            $agg[$k] = round($agg[$k] / $count, 4);
        }

        $previous = $this->getStoredIdeology($universe);
        $store = (bool) config('worldos.ideology_evolution.store_in_state_vector', true);
        if ($store) {
            $this->storeIdeologyInState($universe, $agg, $previous);
            $this->storeConversionRate($universe, $agg, $previous);
        }

        return [
            'dominant' => $agg,
            'institution_count' => $count,
            'previous_dominant' => $previous,
        ];
    }

    /**
     * If dominant ideology shifted significantly, optionally create an ideology_shift chronicle.
     */
    public function recordShiftIfSignificant(Universe $universe, int $tick, array $current, ?array $previous): void
    {
        if ($previous === null || empty($previous)) {
            return;
        }
        $delta = 0.0;
        foreach (self::IDEOLOGY_KEYS as $k) {
            $delta += abs((float) ($current[$k] ?? 0.5) - (float) ($previous[$k] ?? 0.5));
        }
        $delta /= count(self::IDEOLOGY_KEYS);
        if ($delta < 0.15) {
            return;
        }
        try {
            Chronicle::create([
                'universe_id' => $universe->id,
                'from_tick' => $tick,
                'to_tick' => $tick,
                'type' => 'ideology_shift',
                'content' => 'Chuyển dịch hệ tư tưởng: ' . json_encode($current, JSON_UNESCAPED_UNICODE),
                'raw_payload' => ['previous' => $previous, 'current' => $current],
            ]);
        } catch (\Throwable $e) {
            Log::warning("IdeologyEvolutionEngine: record shift failed: " . $e->getMessage());
        }
    }

    protected function getStoredIdeology(Universe $universe): ?array
    {
        $vec = (array) ($universe->state_vector ?? []);
        $stored = $vec['dominant_ideology'] ?? null;
        return is_array($stored) ? $stored : null;
    }

    protected function storeIdeologyInState(Universe $universe, array $dominant, ?array $previous): void
    {
        $vec = (array) ($universe->state_vector ?? []);
        $vec['dominant_ideology'] = $dominant;
        if ($previous !== null) {
            $vec['previous_dominant_ideology'] = $previous;
        }
        $universe->state_vector = $vec;
        $universe->save();
    }

    /** Doc §10: Store conversion probability (ideology drift) in state. */
    protected function storeConversionRate(Universe $universe, array $current, ?array $previous): void
    {
        if ($previous === null || empty($previous)) {
            return;
        }
        $rate = $this->conversionService->conversionProbability($universe, $previous, $current);
        $vec = (array) ($universe->state_vector ?? []);
        $vec['ideology_conversion'] = ['rate_per_tick' => $rate];
        $universe->state_vector = $vec;
        $universe->save();
    }
}


