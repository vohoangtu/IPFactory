<?php

namespace App\Modules\WorldOS\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchComparisonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'universe_id' => (int) ($this->resource['universe_id'] ?? 0),
            'branch_id' => (int) ($this->resource['branch_id'] ?? 0),
            'source' => $this->resource['source'] ?? [],
            'branch' => $this->resource['branch'] ?? [],
            'tick_span' => (int) ($this->resource['tick_span'] ?? 0),
            'deltas' => $this->resource['deltas'] ?? [],
            'metric_deltas' => $this->resource['metric_deltas'] ?? [],
        ];
    }
}
