<?php

namespace App\Modules\Narrative\Services;

use App\Modules\Simulation\Entities\UniverseEntity;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Contracts\LlmNarrativeClientInterface;
use App\Modules\Narrative\Repositories\ChronicleMemoryRepository;
use App\Modules\Narrative\Dto\NarrativeProjection;
use App\Modules\Narrative\Dto\NarrativeMeaning;
use App\Modules\Narrative\Models\NarrativeState;
use App\Modules\Intelligence\Services\CollectiveUnconsciousService;
use Illuminate\Support\Facades\Log;

/**
 * NarrativeEngine: The main orchestrator for the narrative pipeline.
 * V2: Implements the "Interpreter vs System Brain" paradigm.
 */
class NarrativeEngine
{
    public function __construct(
        protected StateExtractorDSL $extractor,
        protected SignalExtractor $signalExtractor,
        protected SignalBuilder $signalBuilder,
        protected NarrativeScheduler $scheduler,
        protected StateMutationEngine $mutationEngine,
        protected ChronicleMemoryRepository $memoryRepository,
        protected LlmNarrativeClientInterface $llmClient,
        protected NarrativeFeedbackService $feedbackService,
        protected \App\Modules\SocialGraph\Services\Neo4jSocialSyncer $graphSyncer,
        protected CollectiveUnconsciousService $psychologyService
    ) {}

    /**
     * Run the narrative pipeline for a specific simulation tick.
     */
    public function pulse(UniverseEntity $universe, UniverseSnapshot $snapshot): void
    {
        try {
            // 0. Adaptive Scheduler: Skip if state hasn't changed enough
            if (!$this->scheduler->shouldPulse($universe, $snapshot)) {
                return;
            }

            Log::info("NarrativeEngine: Pulse started for Universe {$universe->id} at tick {$snapshot->tick}");

            // 1. Extract Narrative Context (Tokens + Events)
            $contextData = $this->extractor->extractContext(
                $universe->id, 
                $snapshot->tick, 
                $snapshot->state_vector ?? [], 
                $snapshot->metrics ?? []
            );
            $tokens = $contextData['tokens'];
            $events = $contextData['events'];
            
            // 2. Manage Narrative State (Arc, Conflicts)
            /** @var NarrativeState $state */
            $state = NarrativeState::firstOrCreate(
                ['universe_id' => $universe->id],
                ['current_arc' => 'Genesis', 'active_conflicts' => []]
            );

            // 3. Build context from memory (History)
            $history = $this->memoryRepository->getContext($universe->id, $tokens);
            
            // 3b. Extract Graph Anomalies (Neo4j)
            $graphCliques = $this->graphSyncer->findAnomalousCliques($universe->id);
            
            // 3c. Extract Collective Psychology (8D Motivation)
            $psychology = $this->psychologyService->calculate($snapshot->universe);
            
            // 4. Construct Multi-Perspective Prompt (Emergent Focus)
            $prompt = $this->buildEmergentPrompt($universe, $state, $history, $events, $tokens, $graphCliques, $psychology);
            
            // 5. Single LLM Call (The Interpretation phase)
            $response = $this->llmClient->generate($prompt);
            
            if (!$response) {
                Log::error("NarrativeEngine: LLM returned empty response for Universe {$universe->id}");
                return;
            }

            // 6. Parse Narrative Meaning (AI interpretation)
            /** @var NarrativeMeaning $meaning */
            $meaning = $this->signalExtractor->extract($response);
            
            // 7. Build Deterministic Signals (The System Brain phase)
            $signal = $this->signalBuilder->build($meaning);
            
            // 8. Apply deterministic state mutations
            $this->mutationEngine->apply($universe, [
                'entropy' => $signal->entropyDelta,
                'stability' => $signal->stabilityDelta
            ]);
            
            // 9. Store new Chronicle with memory index
            $this->memoryRepository->store($universe->id, $snapshot->tick, $meaning);

            // 10. Update Narrative State for continuity
            $state->update([
                'active_conflicts' => array_slice($meaning->keyFactors, 0, 5),
                'last_tick' => $snapshot->tick
            ]);

            // 11. Apply Narrative Feedback (Omens)
            if (!empty($meaning->omens)) {
                $this->feedbackService->applyOmens($universe, $meaning->omens);
            }

            Log::info(
                "NarrativeEngine: Pulse completed for Universe {$universe->id} at tick {$snapshot->tick}",
                [
                    'direction' => $meaning->direction,
                    'tension' => $meaning->tension,
                    'key_factor_count' => count($meaning->keyFactors),
                    'omen_count' => count($meaning->omens),
                ]
            );

        } catch (\Throwable $e) {
            Log::error("NarrativeEngine: Pipeline failed for Universe {$universe->id}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }

    /**
     * Build an Emergent-focused prompt that prioritizes causal events over flat metrics.
     */
    protected function buildEmergentPrompt(UniverseEntity $universe, NarrativeState $state, string $history, array $events, array $tokens, array $cliques = [], array $psychology = []): string
    {
        $eventsText = empty($events) ? "Không có sự kiện đáng chú ý." : collect($events)->map(fn($e) => "- [Tick {$e['tick']}] {$e['summary']}")->implode("\n");
        $tokensText = implode(', ', $tokens);
        
        $graphText = empty($cliques) ? "Không có biến động xã hội đặc biệt." : collect($cliques)->map(fn($c) => "- Cấu trúc mạng lưới xung quanh {$c['name']} (ID: {$c['actor_id']}) đang có {$c['fear_connections']} kết nối sợ hãi dày đặc.")->implode("\n");

        $psychologyText = collect($psychology)->map(fn($val, $key) => "- " . ucfirst($key) . ": " . number_format($val * 100, 1) . "%")->implode("\n");
        
        return <<<EOT
Bạn là Narrative Engine (Kiến trúc Sáng tạo) của WorldOS. Nhiệm vụ của bạn là dệt nên câu chuyện từ các sự kiện đột biến (Emergent Events) thay vì chỉ đọc số liệu khô khan.

VAI TRÒ: Biên niên sử gia của đa vũ trụ.
VŨ TRỤ: {$universe->name} (Chương: {$state->current_arc})

DỮ LIỆU THỰC TẠI (GROUND TRUTH):
Các sự kiện vừa xảy ra trong mô phỏng:
{$eventsText}

TÂM TRẠNG TẬP THỂ (COLLECTIVE PSYCHOLOGY):
{$psychologyText}

BIẾN ĐỘNG MẠNG LƯỚI XÃ HỘI (GRAPH INSIGHTS):
{$graphText}

BỐI CẢNH TOÀN CỤC (TOKEN):
{$tokensText}

BIÊN NIÊN SỬ GẦN ĐÂY:
{$history}

YÊU CẦU:
1. DIỄN GIẢI CAUSALITY: Liên kết các sự kiện và mạng lưới xã hội trên thành một chuỗi nhân quả hợp lý. Đừng chỉ liệt kê, hãy kể về hậu quả của những sự kiện này. Đặc biệt chú ý đến cách các biến động vật chất/xã hội tác động đến "Tâm trạng tập thể" trên.
2. NARRATIVE FEEDBACK: Nếu các sự kiện trên cho thấy một xu hướng mới (ví dụ: bạo lực gia tăng), hãy đề xuất các "Omen" (Điềm báo) để tác động ngược lại mô phỏng.

PHẢI TRẢ VỀ JSON NGHIÊM NGẶT:
{
  "summary": "Mô tả nhân quả sống động (Tiếng Việt)...",
  "tension": "low | medium | high",
  "direction": "growth | stagnation | collapse",
  "key_factors": ["Tag nhân vật/sự kiện chính"],
  "omens": [
    {
      "type": "crisis | oracle | mutation",
      "description": "Mô tả điềm báo sẽ tiêm vào simulation",
      "intensity": 0.5
    }
  ]
}
EOT;
    }

    /**
     * Legacy compatibility: Generate for a single chronicle.
     */
    public function generateForChronicle($chronicle): void {}

    /**
     * Legacy compatibility: Generate batched chronicles.
     */
    public function generateBatched($chronicles, int $windowSize): void {}
}
