<?php

namespace App\Modules\Simulation\Core\Supervisor\Handlers;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Core\Supervisor\Contracts\PostSnapshotHandlerInterface;
use App\Modules\Simulation\Services\Society\SocialGraphService;

final class SocialGraphPostSnapshotHandler implements PostSnapshotHandlerInterface
{
    public function __construct(
        private readonly SocialGraphService $socialGraphService,
    ) {}

    public function handle(Universe $universe, UniverseSnapshot $snapshot): void
    {
        $this->socialGraphService->evaluate($universe, (int) $snapshot->tick);
    }
}

