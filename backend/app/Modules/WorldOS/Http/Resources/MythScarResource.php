<?php

namespace App\Modules\WorldOS\Http\Resources;

use App\Modules\WorldOS\Http\Resources\Support\WorldOsResourceSupport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MythScarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->name,
            'name' => $this->name,
            'severity' => WorldOsResourceSupport::scarSeverity($this->severity),
            'severity_score' => (float) $this->severity,
            'origin_tick' => (int) $this->createdAtTick,
            'created_at_tick' => (int) $this->createdAtTick,
            'consequence' => $this->description,
            'description' => $this->description,
            'zone_id' => $this->zoneId,
            'resolved_at_tick' => $this->resolvedAtTick,
        ];
    }
}
