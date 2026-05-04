<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Contracts\SimulationEngine;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use Illuminate\Support\Facades\Log;

/**
 * Phase 24: Mythogenesis Engine 🎭✨
 * 
 * "Mọi chi tiết của cả vũ trụ đều nằm trong mỗi mảnh dữ liệu."
 * Mô phỏng quá trình Sự kiện -> Câu chuyện -> Niềm tin -> Biểu tượng.
 * Biến các biến cố lớn thành "Linh hồn văn hóa".
 */
class MythogenesisEngine implements SimulationEngine
{
    use DefaultSimulationEnginePhase;

    public function __construct(
        private readonly \App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService $ruleVm
    ) {}

    public function name(): string
    {
        return 'mythogenesis';
    }

    public function phase(): string
    {
        return 'meta';
    }

    public function priority(): int
    {
        return 24;
    }

    public function tickRate(): int
    {
        return 5;
    }

    /**
     * Evaluate current state and return result.
     */
    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $tick = $ctx->getTick();
        $universeId = $ctx->getUniverseId();
        $seed = (int)$state->get('seed', 0);
        $rng = new \App\Modules\Intelligence\Domain\Rng\SimulationRng($seed, $tick, 777); // Magic number for Mythogenesis

        // 1. Lấy các sự kiện gần đây (Chronicles) từ state hoặc DB
        // Chúng ta giả định HistoricalFact chứa các sự kiện quan trọng
        $recentFacts = \App\Modules\Narrative\Models\HistoricalFact::where('universe_id', $universeId)
            ->where('tick', $tick)
            ->get();

        $activeMyths = $state->get('meta.active_myths', []);

        foreach ($recentFacts as $fact) {
            $impact = $this->calculateImpact($fact);
            
            // Phase 12: Generative Literature
            // Nếu tác động cực lớn, sinh ra Cultural Artifact (Sử thi/Hệ tư tưởng)
            if ($impact > 0.9) {
                $this->generateCulturalArtifact($fact, $rng);
            }

            // Nếu tác động vượt ngưỡng, bắt đầu quá trình thần thoại hóa
            if ($impact > 0.75) {
                $archetype = $this->determineArchetype($fact, $rng);
                $myth = \App\Modules\Narrative\Models\Myth::create([
                    'universe_id' => $universeId,
                    'myth_type' => $archetype,
                    'story' => $this->generateStory($fact, $archetype),
                    'source_events' => [$fact->id],
                    'impact' => $impact,
                ]);

                $activeMyths[] = [
                    'id' => $myth->id,
                    'archetype' => $archetype,
                    'belief_strength' => 0.5,
                    'symbolic_power' => $impact,
                ];

                Log::info("Mythogenesis: A new myth has been born from Fact #{$fact->id}", ['archetype' => $archetype]);
            }
        }

        // 2. Myth Evolution & Decay
        foreach ($activeMyths as &$m) {
            // Sử dụng RNG để mô phỏng sự biến đổi câu chuyện (Story Drift)
            if ($rng->nextFloat() < 0.05) {
                $m['symbolic_power'] *= 1.05;
                $m['belief_strength'] = min(1.0, $m['belief_strength'] + 0.02);
            }

            // Suy tàn tự nhiên
            $m['belief_strength'] *= 0.995;
        }

        $state->set('meta.active_myths', $activeMyths);

        // 3. DSL Layer: Áp dụng quy tắc từ myth.dsl (Nếu có)
        $this->applyDslRules($state, $tick);

        return new EngineResult([], [], []);
    }

    private function calculateImpact(\App\Modules\Narrative\Models\HistoricalFact $fact): float
    {
        // Impact dựa trên số lượng actor bị ảnh hưởng và danh mục sự kiện
        $actorCount = count($fact->actors ?? []);
        $baseImpact = match($fact->category) {
            'WAR' => 0.6,
            'DISCOVERY' => 0.5,
            'RELIGION' => 0.7,
            'CRISIS' => 0.8,
            default => 0.3
        };

        return min(1.0, $baseImpact + ($actorCount / 1000));
    }

    private function determineArchetype(\App\Modules\Narrative\Models\HistoricalFact $fact, $rng): string
    {
        $archetypes = ['HERO', 'MARTYR', 'CREATOR', 'DESTROYER', 'OIKOS'];
        
        if ($fact->category === 'WAR') return 'MARTYR';
        if ($fact->category === 'DISCOVERY') return 'CREATOR';
        
        return $archetypes[$rng->nextInt(0, count($archetypes) - 1)];
    }

    private function generateStory(\App\Modules\Narrative\Models\HistoricalFact $fact, string $archetype): string
    {
        // Placeholder cho việc generate story (Sau này có thể tích hợp AI)
        return "Huyền thoại về một {$archetype} xuất hiện từ sự kiện {$fact->category} tại Tick #{$fact->tick}.";
    }

    private function generateCulturalArtifact(\App\Modules\Narrative\Models\HistoricalFact $fact, $rng): void
    {
        $type = $fact->category === 'WAR' ? 'EPIC' : 'IDEOLOGY';
        $name = $this->generateTitle($fact, $type, $rng);

        \App\Modules\Narrative\Models\CulturalArtifact::create([
            'universe_id' => $fact->universe_id,
            'author_id' => $fact->actors[0] ?? null, // Khớp với model
            'type' => $type,
            'name' => $name, // Khớp với model
            'power_level' => 0.3, // Khớp với model
            'properties' => [ // Khớp với model
                'content' => $this->generateContent($fact, $type),
                'source_fact_id' => $fact->id,
                'trait_modifiers' => $type === 'EPIC' ? ['power' => 0.1] : ['meaning' => 0.1],
            ],
            'created_at_tick' => $fact->tick,
            'is_active' => true,
        ]);

        Log::alert("CULTURAL LEGACY: A new {$type} titled '{$name}' has been written!");
    }

    private function generateTitle(\App\Modules\Narrative\Models\HistoricalFact $fact, string $type, $rng): string
    {
        $prefixes = ['Sử thi', 'Khải huyền', 'Bản thảo', 'Hệ tư tưởng'];
        $core = $fact->category;
        return $prefixes[$rng->nextInt(0, 3)] . " về " . $core . " tại " . $fact->location;
    }

    private function generateContent(\App\Modules\Narrative\Models\HistoricalFact $fact, string $type): string
    {
        return "Tác phẩm này ghi lại sự kiện {$fact->category} diễn ra tại Tick {$fact->tick}, nó sẽ được truyền tụng qua nhiều kỷ nguyên.";
    }

    private function applyDslRules(WorldState $state, int $tick): void
    {
        $this->ruleVm->evaluateAndApplyWithDsl(
            $state,
            'culture/myth.dsl',
            $tick,
            ['mode' => 'MYTH_GENERATION']
        );
    }
}


