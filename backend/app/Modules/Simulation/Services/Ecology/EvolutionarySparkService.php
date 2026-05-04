<?php

namespace App\Modules\Simulation\Services\Ecology;

use App\Modules\Narrative\Models\HistoricalFact;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Intelligence\Domain\Rng\SimulationRng;
use Illuminate\Support\Facades\Log;

/**
 * EvolutionarySparkService: Injects "Creativity Sparks" into stagnant universes.
 * Prevents "Macro Compression" by ensuring qualitative growth even in small populations.
 */
class EvolutionarySparkService
{
    /**
     * Check if a universe needs a spark and inject it if necessary.
     */
    public function spark(Universe $universe, int $tick, ?UniverseSnapshot $snapshot = null): void
    {
        $entropy = $universe->entropy ?? 0.5;
        $stability = $universe->structural_coherence ?? 1.0;
        
        // Stagnation Detection: Low entropy + High stability + Low tick activity
        if ($entropy < 0.2 && $stability > 0.8) {
            $this->injectCreativity($universe, $tick);
        }
    }

    protected function injectCreativity(Universe $universe, int $tick): void
    {
        $rng = new SimulationRng($universe->seed ?? 0, $tick, 999); // 999: Spark entropy seed
        
        // Randomly choose between Discovery or Religion spark
        $categories = ['DISCOVERY', 'RELIGION'];
        $category = $categories[$rng->nextFloat() < 0.5 ? 0 : 1];
        
        $descriptions = [
            'DISCOVERY' => [
                'Một tia sáng ý thức bùng lên: Lửa đã được thuần hóa.',
                'Sự tò mò dẫn lối: Một công cụ mới từ đá đã thành hình.',
                'Quan sát bầu trời: Sự khởi đầu của tri thức về thời gian.',
            ],
            'RELIGION' => [
                'Một giấc mơ kỳ lạ: Lời thì thầm của đấng sáng tạo.',
                'Vẻ đẹp của tự nhiên được sùng bái: Ngôi đền đầu tiên được dựng lên.',
                'Cảm nhận về sự vô hạn: Một hệ thống niềm tin sơ khai được hình thành.',
            ]
        ];

        $descArray = $descriptions[$category];
        $index = (int)($rng->nextFloat() * count($descArray));
        $description = $descArray[min($index, count($descArray) - 1)];

        // Create a HistoricalFact to trigger Mythogenesis/IdeaDiffusion
        $fact = HistoricalFact::create([
            'universe_id' => $universe->id,
            'tick' => $tick,
            'category' => $category,
            'actors' => [], // Collective spark
            'facts' => [
                'type' => 'evolutionary_spark',
                'description' => $description,
                'potency' => 0.9, // High impact to trigger Mythogenesis
            ],
            'metrics_before' => $universe->state_vector,
            'metrics_after' => $universe->state_vector,
        ]);

        Log::info("EvolutionarySpark: Injected {$category} spark into Universe #{$universe->id} at Tick #{$tick}");
    }
}

