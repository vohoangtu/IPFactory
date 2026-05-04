<?php

namespace App\Modules\WorldOS\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActorEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tick' => (int) $this->tick,
            'type' => $this->event_type,
            'summary' => data_get($this->context, 'summary', ucfirst(str_replace('_', ' ', (string) $this->event_type))),
            'context' => $this->context ?? [],
        ];
    }
}
