<?php

namespace App\Modules\Simulation\Services\Narrative;

use App\Modules\Simulation\Entities\UniverseEntity;
use App\Modules\Simulation\Contracts\UniverseRepositoryInterface;
use App\Modules\Narrative\Models\Chronicle;
use Illuminate\Support\Facades\Log;

class NarrativeFeedbackService
{
    public function __construct(
        private UniverseRepositoryInterface $universeRepository
    ) {}

    /**
     * Chuyển hóa các "Chủ đề tự sự" thành các delta trạng thái thực tại.
     */
    public function applyFeedback(UniverseEntity $universe, Chronicle $chronicle): void
    {
        $content = strtolower($chronicle->content);
        $delta = ['entropy' => 0.0, 'order' => 0.0];

        // NLP đơn giản: Mapping từ khóa tự sự sang tác động thực tại
        $mappings = [
            'hỗn mang' => ['entropy' => 0.01, 'order' => -0.005],
            'chaos' => ['entropy' => 0.01, 'order' => -0.005],
            'trật tự' => ['entropy' => -0.01, 'order' => 0.015],
            'order' => ['entropy' => -0.01, 'order' => 0.015],
            'huyền thoại' => ['entropy' => 0.005, 'order' => 0.005],
            'nỗ lực' => ['order' => 0.01],
            'ánh sáng' => ['entropy' => -0.005, 'order' => 0.02],
        ];

        foreach ($mappings as $keyword => $impact) {
            if (str_contains($content, $keyword)) {
                $delta['entropy'] += $impact['entropy'] ?? 0;
                $delta['order'] += $impact['order'] ?? 0;
            }
        }

        if ($delta['entropy'] != 0 || $delta['order'] != 0) {
            $universe->entropy += $delta['entropy'];
            $universe->stabilityIndex += $delta['order'];
            
            // Đảm bảo không vượt ngưỡng
            $universe->entropy = max(0.0, min(1.0, $universe->entropy));
            $universe->stabilityIndex = max(0.0, min(1.0, $universe->stabilityIndex));
            
            $this->universeRepository->save($universe);
            
            Log::debug("Narrative Feedback applied to Universe {$universe->id}: E=".($delta['entropy'])." O=".($delta['order']));
        }
    }
}

