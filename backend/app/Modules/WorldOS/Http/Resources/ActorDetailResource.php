<?php

namespace App\Modules\WorldOS\Http\Resources;

use Illuminate\Http\Request;

class ActorDetailResource extends ActorSummaryResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'biography' => $this->biography,
            'traits' => $this->traits ?? [],
            'metrics' => $this->metrics ?? [],
            'stats' => $this->stats ?? [],
            'capabilities' => $this->capabilities ?? [],
            'vitality' => $this->vitality ?? [],
            'supreme_entity' => $this->supremeEntity ? (new SupremeEntityResource($this->supremeEntity))->resolve() : null,
            'recent_events' => ActorEventResource::collection(
                $this->events->sortByDesc('tick')->take(5)->values()
            )->resolve(),
        ]);
    }
}
