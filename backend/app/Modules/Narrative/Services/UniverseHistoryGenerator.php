<?php

namespace App\Modules\Narrative\Services;

use App\Modules\Narrative\Contracts\ChronicleRepositoryInterface;
use App\Modules\World\Models\Era;
use App\Modules\Narrative\Models\Legend;
use App\Modules\World\Models\Religion;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseHistory;
use App\Modules\Simulation\Models\CivilizationHistory;
use App\Modules\Simulation\Models\Civilization;
use App\Contracts\LlmNarrativeClientInterface;
use Illuminate\Support\Facades\Log;

/**
 * Aggregates all world-building data to generate a "Complete History" of a Universe.
 */
class UniverseHistoryGenerator
{
    public const MAX_CONTEXT_CHARS = 45000;

    public function __construct(
        protected LlmNarrativeClientInterface $llmClient,
        protected ChronicleSynthesisEngine $synthesisEngine,
        protected ChronicleRepositoryInterface $chronicleRepository
    ) {}

    /**
     * Generate full history for universe.
     */
    public function generate(Universe $universe, ?int $fromTick = null, ?int $toTick = null): ?UniverseHistory
    {
        $toTick = $toTick ?? (int) ($universe->current_tick ?? 0);
        $fromTick = $fromTick ?? 0;

        $context = $this->buildContext($universe->id, $fromTick, $toTick);
        if ($context === '') {
            return null;
        }

        $prompt = "Bạn là Sử gia của WorldOS. Hãy viết một bản 'Lịch sử Toàn thư của Vũ trụ #{$universe->id}' dựa trên các dữ kiện sau.\n\n"
            . "YÊU CẦU:\n"
            . "- Viết 3-5 đoạn văn súc tích.\n"
            . "- Giải thích mối liên hệ nhân quả giữa các sự kiện lớn.\n"
            . "- Ngôn ngữ: Tiếng Việt trang trọng.\n\n"
            . "DỮ KIỆN:\n"
            . $context;

        if (strlen($prompt) > self::MAX_CONTEXT_CHARS) {
            $prompt = substr($prompt, 0, self::MAX_CONTEXT_CHARS) . "... [Dữ liệu bị cắt bớt]";
        }

        $fullText = $this->llmClient->generate($prompt);
        
        if (!$fullText) {
            return null;
        }

        return UniverseHistory::create([
            'universe_id' => $universe->id,
            'full_text' => $fullText,
            'from_tick' => $fromTick,
            'to_tick' => $toTick,
            'generated_at' => now(),
        ]);
    }

    protected function buildContext(int $universeId, int $fromTick, int $toTick): string
    {
        $parts = [];

        // 1. Causal Traces
        $causalLinks = $this->synthesisEngine->synthesize($universeId, $fromTick, $toTick);
        if (!empty($causalLinks)) {
            $parts[] = "NHÂN QUẢ:\n" . implode("\n", $causalLinks);
        }

        // 2. Chronicles
        $chronicles = array_map(
            fn($e) => $e->content,
            $this->chronicleRepository->findByUniverse($universeId, 50)
        );

        if (!empty($chronicles)) {
            $parts[] = "BIÊN NIÊN SỬ:\n" . implode("\n", $chronicles);
        }

        // 3. Eras
        $eras = Era::where('universe_id', $universeId)
            ->whereBetween('start_tick', [$fromTick, $toTick])
            ->get();

        if ($eras->isNotEmpty()) {
            $parts[] = "KỶ NGUYÊN:\n" . $eras->map(fn($e) => "- {$e->title}: {$e->summary}")->implode("\n");
        }

        return implode("\n\n", $parts);
    }
}

