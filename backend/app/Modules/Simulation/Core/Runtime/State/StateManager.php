<?php

namespace App\Modules\Simulation\Core\Runtime\State;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use Illuminate\Support\Facades\Log;

/**
 * StateManager – Facade that delegates to StateLoader and StateWriter.
 *
 * @deprecated Use StateLoader and StateWriter directly in new code.
 *             This class exists solely for backward compatibility during
 *             the migration period and will be removed in a future version.
 */
class StateManager
{
    protected ?WorldState $currentState = null;

    public function __construct(
        protected StateLoader $loader,
        protected StateWriter $writer,
        protected \App\Modules\Simulation\Services\Core\HolographicCompressionService $compressionService,
    ) {
    }

    /**
     * @deprecated Use StateLoader::load() directly.
     */
    public function load(Universe $universe, ?UniverseSnapshot $snapshot = null): WorldState
    {
        $this->currentState = $this->loader->load($universe, $snapshot);

        return $this->currentState;
    }

    /**
     * @deprecated Use StateWriter::save() directly.
     */
    public function save(Universe $universe): void
    {
        if (!$this->currentState) {
            return;
        }

        $this->writer->save($universe, $this->currentState);
    }

    public function get(): ?WorldState
    {
        return $this->currentState;
    }

    /**
     * Clear an actor from current state collections to force reload from DB.
     * (Phase 9: Distributed Consistency)
     */
    public function forgetActor(int|string $actorId): void
    {
        if ($this->currentState) {
            $this->currentState->forgetActor((int) $actorId);
        }
    }
}
