<?php

namespace App\Modules\Narrative\Services;

use App\Modules\Narrative\Contracts\ChronicleRepositoryInterface;
use App\Modules\World\Models\Universe;
use Illuminate\Support\Facades\Log;

/**
 * EtherealOmenService: Generates autonomous omens from internal history (§V20).
 * Replaces external data dependency.
 */
class EtherealOmenService
{
    public function __construct(
        protected ChronicleRepositoryInterface $chronicleRepository
    ) {}
    /**
     * Generate an Omen based on the 'Historical Weight' of a universe.
     */
    public function generateInternalOmen(Universe $universe): array
    {
        // Analyze recent chronicles to find a theme
        $recent = $this->chronicleRepository->findByUniverse($universe->id, 5);
        $recent = collect($recent);
        
        $warCount = $recent->filter(fn($c) => str_contains(strtolower($c->content), 'war') || str_contains(strtolower($c->content), 'conflict'))->count();
        $miracleCount = $recent->filter(fn($c) => $c->type === 'divine_miracle')->count();

        if ($warCount > 2) {
            return [
                'type' => 'Era of Penance',
                'sci_impact' => 0.05,
                'entropy_impact' => -0.1,
                'description' => "Sau chuỗi ngày xung đột, một làn sóng hối lỗi tự thân đang làm dịu đi sự hỗn loạn."
            ];
        }

        if ($miracleCount > 0) {
            return [
                'type' => 'Divine Resonance',
                'sci_impact' => 0.1,
                'entropy_impact' => 0.05,
                'description' => "Dư âm của những phép màu trước đó đang tạo ra một cấu trúc vật lý mới."
            ];
        }

        return [
            'type' => 'Natural Flow',
            'sci_impact' => 0.0,
            'entropy_impact' => 0.0,
            'description' => "Dòng chảy lịch sử đang vận hành một cách tự nhiên."
        ];
    }
}

