<?php

namespace App\Modules\Simulation\Services\Meta;

use App\Modules\World\Models\Universe;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Narrative\Services\NarrativeAiService;
use Illuminate\Support\Facades\Log;

/**
 * ParadoxResolver: Resolves historical contradictions during re-convergence (§V15).
 * Uses AI to create a 'Synthesis Event' out of conflicting timelines.
 */
class ParadoxResolver
{
    public function __construct(
        protected NarrativeAiService $narrative
    ) {}

    /**
     * Resolve contradictions between two histories.
     * Returns a synthesis narrative and the chosen 'Master State' universe.
     */
    public function resolve(Universe $a, Universe $b): array
    {
        $chroniclesA = $a->chronicles()->orderByDesc('to_tick')->limit(5)->get();
        $chroniclesB = $b->chronicles()->orderByDesc('to_tick')->limit(5)->get();

        $contentA = $chroniclesA->pluck('content')->implode("\n");
        $contentB = $chroniclesB->pluck('content')->implode("\n");

        Log::info("PARADOX: Resolving history between Universe #{$a->id} and #{$b->id}");

        // Simulation: Pick the one with higher Structural Coherence as Master
        $master = ($a->structural_coherence >= $b->structural_coherence) ? $a : $b;
        $slave = ($master->id === $a->id) ? $b : $a;

        $prompt = "Synthesis of conflicting timelines:\n\n" .
                  "Timeline A:\n{$contentA}\n\n" .
                  "Timeline B:\n{$contentB}\n\n" .
                  "Create a ONE-SENTENCE mythic synthesis explaining how these realities merged. " .
                  "Keyword: The Great Re-convergence.";

        // In a real environment, this calls NarrativeAiService::generateChronicle()
        $synthesis = "SỰ HỢP NHẤT VĨ ĐẠI: Thực tại của #{$slave->id} đã tan biến vào #{$master->id} theo sự sắp đặt của các tiểu thần.";

        return [
            'master' => $master,
            'slave' => $slave,
            'synthesis' => $synthesis
        ];
    }
}


