<?php

namespace App\Modules\Simulation\Core\SnapshotArchive;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Core\Contracts\SnapshotArchiveInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Archive snapshot JSON to S3/MinIO. Config: worldos.snapshot.archive.disk, .prefix.
 */
final class S3SnapshotArchive implements SnapshotArchiveInterface
{
    public function __construct(
        private readonly string $disk = 's3',
        private readonly string $prefix = 'worldos/snapshots'
    ) {}

    public function archive(Universe $universe, UniverseSnapshot $snapshot): void
    {
        $key = sprintf('%s/u%d/tick_%d.json', $this->prefix, $universe->id, (int) $snapshot->tick);
        $payload = [
            'universe_id' => $universe->id,
            'tick' => $snapshot->tick,
            'entropy' => $snapshot->entropy,
            'stability_index' => $snapshot->stability_index,
            'state_vector' => $snapshot->state_vector,
            'metrics' => $snapshot->metrics ?? [],
        ];
        try {
            Storage::disk($this->disk)->put($key, json_encode($payload));
            Log::debug('S3SnapshotArchive: archived', ['key' => $key]);
        } catch (\Throwable $e) {
            Log::warning('S3SnapshotArchive: failed', ['key' => $key, 'error' => $e->getMessage()]);
        }
    }
}

