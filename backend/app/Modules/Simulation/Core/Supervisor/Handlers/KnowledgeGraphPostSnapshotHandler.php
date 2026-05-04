<?php

namespace App\Modules\Simulation\Core\Supervisor\Handlers;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Core\Supervisor\Contracts\PostSnapshotHandlerInterface;
use App\Modules\Simulation\Services\Core\KnowledgeGraphService;

final class KnowledgeGraphPostSnapshotHandler implements PostSnapshotHandlerInterface
{
    public function __construct(
        private readonly KnowledgeGraphService $knowledgeGraphService,
    ) {}

    public function handle(Universe $universe, UniverseSnapshot $snapshot): void
    {
        $this->knowledgeGraphService->evaluate($universe, (int) $snapshot->tick);
    }
}


