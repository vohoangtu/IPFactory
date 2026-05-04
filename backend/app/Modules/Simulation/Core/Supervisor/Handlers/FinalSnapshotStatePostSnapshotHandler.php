<?php

namespace App\Modules\Simulation\Core\Supervisor\Handlers;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Services\Core\HolographicCompressionService;
use App\Modules\Simulation\Core\Supervisor\Contracts\PostSnapshotHandlerInterface;

/**
 * Ensures UniverseSnapshot stores "final-after-runtime" canonical state.
 *
 * Runtime `StateManager->save()` writes a hologram (delta-encoded) into `universes.state_vector`.
 * This handler decompresses that hologram back into canonical state and persists it into the
 * UniverseSnapshot row used by UI/SSE endpoints.
 */
final class FinalSnapshotStatePostSnapshotHandler implements PostSnapshotHandlerInterface
{
    public function __construct(
        private readonly HolographicCompressionService $compressionService,
    ) {}

    public function handle(Universe $universe, UniverseSnapshot $snapshot): void
    {
        $hologram = (array) ($universe->state_vector ?? []);
        if (!isset($hologram['_hologram'])) {
            // Nothing to canonicalize.
            return;
        }

        $referenceBase = is_array($snapshot->state_vector)
            ? $snapshot->state_vector
            : (array) ($snapshot->state_vector ?? []);

        $canonical = $this->compressionService->decompress($hologram, $referenceBase);

        $snapshot->state_vector = $canonical;

        // Canonical fields we can lift back into dedicated columns.
        if (array_key_exists('entropy', $canonical)) {
            $snapshot->entropy = (float) $canonical['entropy'];
        }

        if (array_key_exists('stability_index', $canonical)) {
            $snapshot->stability_index = (float) $canonical['stability_index'];
        }

        // Optional: if the canonical state also carries metrics, persist them.
        if (array_key_exists('metrics', $canonical) && is_array($canonical['metrics'])) {
            $snapshot->metrics = $canonical['metrics'];
        }

        $snapshot->save();
    }
}


