<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Contracts\SimulationEngine;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use Illuminate\Support\Facades\Log;

/**
 * Phase 26: Knowledge Evolution Engine 🎓🔬
 * 
 * "Khoa học không chỉ là tri thức, mà là quá trình tự sửa đổi của nhận thức thực tại."
 */
class KnowledgeEvolutionEngine implements SimulationEngine
{
    use DefaultSimulationEnginePhase;

    public function __construct(
        private readonly \App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService $ruleVm,
        private readonly \App\Modules\Intelligence\Services\InnovationEngine $innovationEngine
    ) {}

    public function name(): string
    {
        return 'knowledge_evolution';
    }

    public function phase(): string
    {
        return 'meta';
    }

    public function priority(): int
    {
        return 26;
    }

    public function tickRate(): int
    {
        return 5;
    }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $tick = $ctx->getTick();
        $universeId = $ctx->getUniverseId();
        $seed = (int)$state->get('seed', 0);
        $rng = new \App\Modules\Intelligence\Domain\Rng\SimulationRng($seed, $tick, 999); // Magic number for Knowledge

        $knowledgeGraph = $state->get('meta.knowledge_graph', []);
        $innovationPressure = $this->calculateInnovationPressure($state);

        // 1. Discovery Mechanism: Khám phá Paradigm khoa học mới
        // Áp dụng hệ số điều chỉnh từ InnovationEngine (nếu có)
        $innovationModifier = $this->innovationEngine->getInnovationModifier($state->get('meta.innovation_metrics', []));
        
        if ($innovationPressure * $innovationModifier > 0.75) {
            $this->discoverParadigm($universeId, $knowledgeGraph, $tick, $rng);
        }

        // 2. Diffusion: Lan truyền tri thức (Adoption)
        foreach ($knowledgeGraph as &$node) {
            $education = (float)$state->get('fields.knowledge', 0.1);
            $node['adoption'] = min(1.0, ($node['adoption'] ?? 0.1) + ($education * 0.01) + ($rng->nextFloat() * 0.005));
        }

        $state->set('meta.knowledge_graph', $knowledgeGraph);
        
        // Cập nhật trường Knowledge trong manifold dựa trên mức độ chấp nhận trung bình
        if (count($knowledgeGraph) > 0) {
            $avgAdoption = array_reduce($knowledgeGraph, fn($carry, $item) => $carry + $item['adoption'], 0) / count($knowledgeGraph);
            $state->set('fields.knowledge', min(1.0, $avgAdoption));
        }

        // 3. DSL Layer: Áp dụng quy tắc từ knowledge.dsl
        $this->applyDslRules($state, $tick);

        return new EngineResult([], [], []);
    }

    private function calculateInnovationPressure(WorldState $state): float
    {
        $needs = (float)$state->get('meta.resource_scarcity', 0.5);
        $complexity = (float)$state->get('fields.complexity', 0.5);
        $curiosity = (float)$state->get('meta.cultural_curiosity', 0.5);

        return ($needs * 0.4) + ($complexity * 0.3) + ($curiosity * 0.3);
    }

    private function discoverParadigm(int $universeId, array &$graph, int $tick, $rng): void
    {
        $potentialNodes = [
            'ASTROMETRY', 'METALLURGY', 'AGRICULTURE_V2', 'CALCULUS', 'CAUSAL_LOGIC', 'QUANTUM_AXIOMS'
        ];

        $knownNames = array_column($graph, 'name');
        foreach ($potentialNodes as $name) {
            if (!in_array($name, $knownNames)) {
                $idea = \App\Modules\Knowledge\Models\Idea::create([
                    'universe_id' => $universeId,
                    'theme' => $name,
                    'info_type' => 'science',
                    'influence_score' => 0.1,
                    'followers' => 10,
                    'birth_tick' => $tick,
                ]);

                $graph[] = [
                    'id' => 'know_' . $idea->id,
                    'name' => $name,
                    'adoption' => 0.1,
                    'db_id' => $idea->id,
                ];

                Log::info("Knowledge Engine: New paradigm discovered: {$name}");
                break;
            }
        }
    }

    private function applyDslRules(WorldState $state, int $tick): void
    {
        $this->ruleVm->evaluateAndApplyWithDsl(
            $state,
            'culture/knowledge.dsl',
            $tick,
            ['mode' => 'KNOWLEDGE_PEAK']
        );
    }
}


