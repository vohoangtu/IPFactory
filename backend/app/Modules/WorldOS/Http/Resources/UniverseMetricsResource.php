<?php

namespace App\Modules\WorldOS\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UniverseMetricsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'universe_id' => (int) ($this->resource['universe_id'] ?? 0),
            'status' => $this->resource['status'] ?? 'paused',
            'current_tick' => (int) ($this->resource['current_tick'] ?? 0),
            'stability' => (float) ($this->resource['stability'] ?? 0),
            'entropy' => (float) ($this->resource['entropy'] ?? 0),
            'snapshot_count' => (int) ($this->resource['snapshot_count'] ?? 0),
            'branch_count' => (int) ($this->resource['branch_count'] ?? 0),
            'actor_count' => (int) ($this->resource['actor_count'] ?? 0),
            'chronicle_count' => (int) ($this->resource['chronicle_count'] ?? 0),
            'anomaly_count' => (int) ($this->resource['anomaly_count'] ?? 0),
            'myth_count' => (int) ($this->resource['myth_count'] ?? 0),
            'religion_count' => (int) ($this->resource['religion_count'] ?? 0),
            'material_identity' => $this->resource['material_identity'] ?? [],
            'culture_identity' => $this->resource['culture_identity'] ?? [],
        ];
    }
}
