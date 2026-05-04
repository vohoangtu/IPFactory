<?php

namespace App\Actions\AI;

use App\Modules\World\Models\Universe;
use App\Modules\Intelligence\Services\AI\TheorySynthesisService;
use Illuminate\Support\Facades\Log;

/**
 * TheorySynthesisAction: Triggers the proactive discovery of new world axioms.
 * Part of Phase 56: Epistemic Discovery Engine (§V7).
 */
class TheorySynthesisAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        protected TheorySynthesisService $synthesisService
    ) {}

    /**
     * Execute theory synthesis for a universe.
     * Usually triggered when stability is low or a major crisis occurs.
     */
    public function execute(Universe $universe): array
    {
        Log::info("Executing Theory Synthesis for Universe {$universe->id}");

        try {
            $axiom = $this->synthesisService->synthesizeTheory($universe);
            
            if ($axiom) {
                return [
                    'ok' => true,
                    'discovered' => true,
                    'axiom' => $axiom,
                    'message' => "Hệ thống đã phát hiện một quy luật mới tiềm ẩn: {$axiom->axiom_key}"
                ];
            }

            return [
                'ok' => true,
                'discovered' => false,
                'message' => 'Không tìm thấy quy luật mới có ý nghĩa tại thời điểm này.'
            ];
        } catch (\Exception $e) {
            Log::error("Theory Synthesis Failed: " . $e->getMessage());
            return [
                'ok' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

