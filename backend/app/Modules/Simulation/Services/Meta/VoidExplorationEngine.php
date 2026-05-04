<?php

namespace App\Modules\Simulation\Services\Meta;

use App\Modules\Simulation\Entities\UniverseEntity;
use App\Modules\Simulation\Actions\ManifestRelicAction;
use Illuminate\Support\Facades\Log;

class VoidExplorationEngine
{
    public function __construct(
        protected ManifestRelicAction $manifestRelicAction
    ) {}

    /**
     * Phân tích các rạn nứt trong Hư Không và phát hiện cổ vật.
     */
    public function process(UniverseEntity $universe, int $tick): void
    {
        // Tỷ lệ xuất hiện cổ vật dựa trên Entropy (Càng loạn càng dễ ra đồ lạ)
        $discoveryChance = $universe->entropy * 0.01; 
        
        if (mt_rand() / mt_getrandmax() < $discoveryChance) {
            $this->triggerDiscovery($universe, $tick);
        }
    }

    protected function triggerDiscovery(UniverseEntity $universe, int $tick): void
    {
        Log::info("Void Rift detected in Universe {$universe->id} at tick {$tick}");
        
        // Mock data cho Relic (Trong thực tế sẽ lấy từ preset hoặc pool huyền thoại)
        $relicData = [
            'name' => 'Mảnh Vỡ Hư Không',
            'rarity' => 'legendary',
            'description' => 'Một mảnh vỡ của thực tại đã sụp đổ, chứa đựng những ký ức của một kỷ nguyên chưa từng tồn tại.',
            'power_vector' => ['entropy_shield' => 0.5, 'truth_vision' => 0.8],
            'metadata' => ['source' => 'void_fracture']
        ];

        $this->manifestRelicAction->execute($universe, $tick, $relicData);
    }
}
