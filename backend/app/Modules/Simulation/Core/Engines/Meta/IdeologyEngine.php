<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\Narrative\Models\HistoricalFact;
use App\Modules\Narrative\Models\CulturalArtifact;
use App\Modules\Simulation\Core\Contracts\SimulationEngine;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use Illuminate\Support\Facades\Log;

/**
 * Phase 13: Ideology Engine 🧠🚩
 * 
 * "Vết sẹo của thế hệ này là lý tưởng của thế hệ sau."
 * Converts traumas (Historical Scars) into long-term Cultural Ideologies.
 */
class IdeologyEngine implements SimulationEngine
{
    public function name(): string
    {
        return 'ideology_evolution';
    }

    public function phase(): string
    {
        return 'meta';
    }

    public function priority(): int
    {
        return 14; // After Mythogenesis and Cultural Influence
    }

    public function tickRate(): int
    {
        return 20; // Ideologies evolve slowly
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function isParallelSafe(): bool
    {
        return true;
    }

    public function priorityCategory(): string
    {
        return 'STOCHASTIC';
    }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $universeId = $ctx->getUniverseId();
        $tick = $ctx->getTick();
        
        // 1. Retrieve Scars from WorldState
        $scars = $state->getScars();
        if (empty($scars)) {
            return new EngineResult([], [], []);
        }

        foreach ($scars as $scar) {
            // A scar has: type, magnitude, duration, source_fact_id
            $magnitude = $scar['magnitude'] ?? 0;
            
            // 2. Chance to ignite into an Ideology if magnitude is high (> 0.8)
            if ($magnitude > 0.8 && rand(1, 100) > 95) {
                $this->igniteIdeology($universeId, $scar, $tick);
            }
        }

        return new EngineResult([], [], []);
    }

    private function igniteIdeology(int $universeId, array $scar, int $tick): void
    {
        $type = $scar['type'] ?? 'TRAUMA';
        $sourceFact = HistoricalFact::find($scar['source_fact_id'] ?? 0);
        
        if (!$sourceFact) return;

        $name = $this->generateIdeologyName($type, $sourceFact->location);
        
        // Check if similar ideology exists
        $exists = CulturalArtifact::where('universe_id', $universeId)
            ->where('type', 'IDEOLOGY')
            ->where('name', $name)
            ->exists();

        if ($exists) return;

        CulturalArtifact::create([
            'universe_id' => $universeId,
            'type' => 'IDEOLOGY',
            'name' => $name,
            'power_level' => 0.5, // Start with moderate influence
            'author_id' => null, // Ideologies are collective
            'properties' => [
                'content' => "Một hệ tư tưởng nảy sinh từ biến cố {$sourceFact->category} tại {$sourceFact->location}.",
                'trait_modifiers' => $this->mapScarToModifiers($type),
                'origin_scar_type' => $type,
                'born_at_tick' => $tick
            ],
            'created_at_tick' => $tick,
            'is_active' => true
        ]);

        Log::alert("IDEOLOGY BORN: '{$name}' has emerged from the historical scars of Universe [{$universeId}]");
    }

    private function generateIdeologyName(string $type, string $location): string
    {
        $suffixes = [
            'TRAUMA' => ['Chủ nghĩa Hiện sinh', 'Sự Khắc kỷ', 'Chủ nghĩa Phục hận'],
            'VICTORY' => ['Chủ nghĩa Ưu việt', 'Sự Bành trướng', 'Thời đại Hoàng kim'],
            'FAMINE' => ['Sự Tiết kiệm', 'Cộng đồng tương trợ', 'Lòng vị tha'],
            'DISCOVERY' => ['Chủ nghĩa Khai sáng', 'Sự Duy lý', 'Chủ nghĩa Tiến bộ']
        ];

        $list = $suffixes[$type] ?? ['Chủ nghĩa Mới'];
        return $list[array_rand($list)] . " tại " . $location;
    }

    private function mapScarToModifiers(string $type): array
    {
        return match($type) {
            'TRAUMA' => ['survival' => 0.2, 'power' => 0.1, 'meaning' => -0.1],
            'VICTORY' => ['status' => 0.2, 'power' => 0.2, 'belonging' => 0.1],
            'FAMINE' => ['survival' => 0.3, 'wealth' => 0.2, 'belonging' => 0.1],
            'DISCOVERY' => ['knowledge' => 0.3, 'meaning' => 0.2],
            default => ['meaning' => 0.1]
        };
    }
}

