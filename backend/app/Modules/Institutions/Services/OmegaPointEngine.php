<?php

namespace App\Modules\Institutions\Services;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Contracts\UniverseRepositoryInterface;
use Illuminate\Support\Facades\Log;

class OmegaPointEngine
{
    public function __construct(
        protected UniverseRepositoryInterface $universeRepo
    ) {}

    /**
     * Kiểm tra và kích hoạt Điểm Omega nếu vũ trụ đạt tới trạng thái Apotheosis.
     */
    public function process(Universe $universe, UniverseSnapshot $snapshot): void
    {
        $metrics = $snapshot->metrics ?? [];
        $order = (float)($metrics['order'] ?? 0);
        $energy = (float)($metrics['energy_level'] ?? 0);
        $innovation = (float)(($snapshot->state_vector ?? [])['innovation'] ?? 0);

        // Điều kiện Apotheosis: Trật tự cao, Năng lượng dồi dào và Đổi mới đạt cực hạn
        if ($order > 0.95 && $energy > 0.8 && $innovation > 0.99) {
            $this->triggerApotheosis($universe, $snapshot);
        }
    }

    protected function triggerApotheosis(Universe $universe, UniverseSnapshot $snapshot): void
    {
        if ($universe->status === 'apotheosis') {
            return;
        }

        Log::emergency("OMEGA POINT REACHED: Universe {$universe->id} is entering Apotheosis.");

        // Cập nhật trạng thái vũ trụ
        $this->universeRepo->update($universe->id, [
            'status' => 'apotheosis',
            'state_vector' => array_merge($universe->state_vector ?? [], [
                'omega_point_reached' => true,
                'is_frozen' => true
            ])
        ]);

        // Tạo sự kiện vĩ mô
        \App\Modules\Narrative\Models\Chronicle::create([
            'universe_id' => $universe->id,
            'from_tick' => $snapshot->tick,
            'to_tick' => $snapshot->tick,
            'type' => 'myth',
            'raw_payload' => [
            'action' => 'legacy_event',
            'description' => 'ĐIỂM OMEGA ĐÃ THIẾT LẬP. Mọi dòng thời gian hội tụ, mọi ý thức hợp nhất làm một. Thực tại đã đạt tới sự thăng hoa tuyệt đối và bước vào trạng thái tĩnh tại vĩnh hằng.'
        ]
        ]);

        event(new \App\Modules\Simulation\Events\AnomalyDetected($universe, [
            'title' => 'SỰ THĂNG HOA TUYỆT ĐỐI (APOTHEOSIS)',
            'description' => 'Vũ trụ đã đạt tới Điểm Omega. Toàn bộ thực tại đang hợp nhất vào một ý thức thống nhất.',
            'severity' => 'CRITICAL'
        ]));
    }
}


