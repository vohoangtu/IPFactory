<?php

namespace App\Modules\Simulation\Core\Domain\Actions;

use App\Modules\Simulation\Contracts\WorldRepositoryInterface;
use App\Modules\Simulation\Entities\WorldEntity;
use App\Modules\Simulation\Services\Cosmology\AxiomaticUniverseCreator;
use App\Modules\Simulation\Services\Core\ImplicitOrchestratorService;
use Illuminate\Support\Facades\Log;

/**
 * InitializeUniverseAction — Single-responsibility Action cho việc khởi tạo Universe.
 * 
 * Đóng gói toàn bộ quy trình: tạo World → tạo Universe → áp dụng Axioms.
 * Tuân thủ Action Pattern (SOLID - SRP): mỗi Action chỉ làm MỘT việc duy nhất.
 */
final class InitializeUniverseAction
{
    public function __construct(
        private WorldRepositoryInterface $worldRepository,
        private ImplicitOrchestratorService $orchestrator,
        private AxiomaticUniverseCreator $axiomCreator,
    ) {}

    /**
     * Khởi tạo một Universe mới trong World đã cho.
     *
     * @param string $worldName       Tên World (tạo mới nếu chưa có).
     * @param array  $worldAttributes Các field bổ sung cho World.
     * @param string $axiomTemplate   Template axiom ('realism', 'wuxia', 'xuanhuan', 'apocalyptic').
     * @return array{world: WorldEntity, universe_id: int}
     */
    public function execute(
        string $worldName,
        array $worldAttributes = [],
        string $axiomTemplate = 'realism',
    ): array {
        // 1. Tìm hoặc tạo World
        $world = $this->worldRepository->firstOrCreate($worldName, $worldAttributes);
        Log::info("InitializeUniverseAction: World resolved", ['world_id' => $world->id]);

        // 2. Spawn Universe qua Orchestrator
        $worldModel = \App\Modules\World\Models\World::findOrFail($world->id);
        $universe = $this->orchestrator->spawnUniverse($worldModel);
        Log::info("InitializeUniverseAction: Universe spawned", ['universe_id' => $universe->id]);

        // 3. Áp dụng Axioms
        $this->axiomCreator->initialize($universe, $axiomTemplate);
        Log::info("InitializeUniverseAction: Axioms applied", ['template' => $axiomTemplate]);

        return [
            'world' => $world,
            'universe_id' => $universe->id,
        ];
    }
}

