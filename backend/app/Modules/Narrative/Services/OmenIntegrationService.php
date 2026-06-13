<?php

namespace App\Modules\Narrative\Services;

use App\Modules\World\Models\Universe;
use App\Modules\Narrative\Contracts\ChronicleRepositoryInterface;
use App\Contracts\Repositories\UniverseSnapshotRepositoryInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Log;
use App\Modules\Intelligence\Services\AI\AnalyticalAiService;

/**
 * OmenIntegrationService: Bridges the gap between reality and simulation (§V18).
 * Fetches external data or generates LLM-driven contextual anomalies to influence the multiverse state.
 */
class OmenIntegrationService
{
    public function __construct(
        protected AnalyticalAiService $analyticalAi,
        protected UniverseSnapshotRepositoryInterface $snapshotRepository,
        protected CacheRepository $cache,
        protected ChronicleRepositoryInterface $chronicleRepository
    ) {}

    /**
     * Get the current 'Cosmic Omen' based on the contextual simulation state.
     * Uses the LLM to propose a contextual anomaly.
     */
    public function getCurrentOmen(Universe $universe): array
    {
        // Cache the generated omen per universe+tick to avoid multiple LLM calls in the same tick
        $cacheKey = "omen:universe:{$universe->id}:tick:{$universe->current_tick}";

        return $this->cache->remember($cacheKey, 30, function () use ($universe) {
            $context = $this->buildContextPayload($universe);
            
            $prompt = "Bạn là AI 'Omen Weaver' (Người dệt Điềm báo) cho hệ thống WorldOS quản lý vũ trụ.\n" .
                      "Dựa vào bối cảnh [World State] và [Lịch sử sự kiện gần đây] của vũ trụ, hãy đề xuất một 'Omen' (Biến cố / Điềm báo) phù hợp để tiêm vào mô phỏng.\n" .
                      "Chỉ trả về ĐÚNG MỘT JSON với cấu trúc sau (không kèm markdown format ngoài):\n" .
                      "{\n" .
                      "  \"type\": \"Tên biến cố ngắn gọn tiếng Anh (VD: Void Resonance, Golden Era)\",\n" .
                      "  \"description\": \"Mô tả ngắn gọn tiếng Việt (1 câu)\",\n" .
                      "  \"sci_modifier\": Giá trị float từ -0.5 đến 0.5,\n" .
                      "  \"entropy_modifier\": Giá trị float từ -0.5 đến 0.5\n" .
                      "}\n\n" .
                      "Context JSON:\n" . json_encode($context, JSON_UNESCAPED_UNICODE);

            try {
                $response = $this->analyticalAi->generateStructuredProposal($prompt);
                
                if ($response && isset($response['type'], $response['description'], $response['sci_modifier'], $response['entropy_modifier'])) {
                    Log::info("OmenIntegrationService: AI generated contextual Omen.", ['omen_type' => $response['type']]);
                    return [
                        'type'             => $response['type'],
                        'sci_modifier'     => (float) $response['sci_modifier'],
                        'entropy_modifier' => (float) $response['entropy_modifier'],
                        'description'      => $response['description']
                    ];
                }
            } catch (\Exception $e) {
                Log::warning("OmenIntegrationService: LLM failed, falling back to random Omen. Error: " . $e->getMessage());
            }

            return $this->getFallbackOmen($universe);
        });
    }

    /**
     * Apply the current Omen to a World state Edict Payload.
     */
    public function applyOmenToEdict(Universe $universe, array &$edictPayload): void
    {
        $omen = $this->getCurrentOmen($universe);
        
        $edictPayload['sci_impact'] = ($edictPayload['sci_impact'] ?? 0) + $omen['sci_modifier'];
        $edictPayload['entropy_impact'] = ($edictPayload['entropy_impact'] ?? 0) + $omen['entropy_modifier'];
        $edictPayload['omen_type'] = $omen['type'];
        $edictPayload['omen_description'] = $omen['description'];

        Log::info("OMEN: Applied '{$omen['type']}' to divine action.");
    }
    
    /**
     * Builds a summarized payload of the current universe context.
     */
    protected function buildContextPayload(Universe $universe): array
    {
        $snapshot = $this->snapshotRepository->getLatest($universe->id);
        
        $chronicles = array_map(
            fn($e) => [
                'from_tick' => $e->fromTick,
                'type' => $e->type,
                'raw_payload' => $e->rawPayload
            ],
            $this->chronicleRepository->findByUniverse($universe->id, 5)
        );
            
        return [
            'universe_id' => $universe->id,
            'current_tick' => $universe->current_tick,
            'base_genre' => $universe->world->base_genre ?? 'unknown',
            'latest_snapshot_metrics' => [
                'entropy' => $snapshot->entropy ?? 0.5,
                'stability_index' => $snapshot->stability_index ?? 0.5,
            ],
            'recent_history' => $chronicles
        ];
    }

    /**
     * Original random logic as a reliable fallback.
     */
    protected function getFallbackOmen(Universe $universe): array
    {
        $sentiments = ['positive', 'negative', 'neutral', 'volatile'];
        $prng = \App\Support\Simulation\SimulationPRNG::forUniverse($universe);
        $chosen = $prng->randomElement($sentiments);

        $omens = [
            'positive' => [
                'type' => 'Golden Era',
                'sci_modifier' => 0.05,
                'entropy_modifier' => -0.05,
                'description' => "Một kỷ nguyên vàng của sự sáng tạo đang rò rỉ từ Cõi Ngoài."
            ],
            'negative' => [
                'type' => 'Shadow Leak',
                'sci_modifier' => -0.05,
                'entropy_modifier' => 0.1,
                'description' => "Xung đột và bất ổn từ Thế giới Thực đang thấm vào các thực tại."
            ],
            'volatile' => [
                'type' => 'Cosmic Storm',
                'sci_modifier' => -0.1,
                'entropy_modifier' => 0.2,
                'description' => "Những biến động dữ dội từ Cõi Ngoài đang phá vỡ sự ổn định."
            ],
            'neutral' => [
                'type' => 'Steady Flow',
                'sci_modifier' => 0.0,
                'entropy_modifier' => 0.0,
                'description' => "Dòng chảy giữa các thế giới đang ở trạng thái cân bằng."
            ]
        ];

        return $omens[$chosen];
    }
}


