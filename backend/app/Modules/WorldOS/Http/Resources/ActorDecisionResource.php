<?php

namespace App\Modules\WorldOS\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActorDecisionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'actor_id' => (int) $this->actor_id,
            'universe_id' => (int) $this->universe_id,
            'tick' => (int) $this->tick,
            'action_type' => $this->action_type,
            'summary' => $this->reasoning ?: ucfirst(str_replace('_', ' ', (string) $this->action_type)),
            'utility_score' => (float) ($this->utility_score ?? 0),
            'confidence' => (float) ($this->confidence ?? 0),
            'impact' => $this->impact ?? [],
            'context_snapshot' => $this->context_snapshot ?? [],
        ];
    }
}
