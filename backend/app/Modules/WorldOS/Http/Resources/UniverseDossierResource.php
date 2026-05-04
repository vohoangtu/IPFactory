<?php

namespace App\Modules\WorldOS\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UniverseDossierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'universe_id' => (int) ($this->resource['universe_id'] ?? 0),
            'name' => $this->resource['name'] ?? null,
            'tick' => (int) ($this->resource['tick'] ?? 0),
            'status' => $this->resource['status'] ?? 'paused',
            'material_identity' => $this->resource['material_identity'] ?? [],
            'culture_identity' => $this->resource['culture_identity'] ?? [],
            'civilization_profile' => $this->resource['civilization_profile'] ?? [],
            'civilization' => $this->resource['civilization'] ?? [],
            'myths' => $this->resource['myths'] ?? [],
            'religions' => $this->resource['religions'] ?? [],
            'history' => $this->resource['history'] ?? [],
        ];
    }
}
