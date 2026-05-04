<?php

namespace App\Modules\WorldOS\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupremeEntityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'entity_type' => $this->entity_type,
            'domain' => $this->domain,
            'power_level' => (float) ($this->power_level ?? 0),
            'alignment' => $this->alignment ?? [],
            'status' => $this->status,
            'actor_id' => $this->actor_id,
        ];
    }
}
