<?php

namespace App\Modules\WorldOS\Http\Resources;

use App\Modules\WorldOS\Http\Resources\Support\WorldOsResourceSupport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChronicleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $title = $this->rawPayload['title'] ?? null;
        $summary = $this->content ?: 'No summary available.';

        return [
            'id' => $this->id,
            'universe_id' => (int) $this->universeId,
            'tick' => (int) ($this->toTick ?: $this->fromTick),
            'from_tick' => (int) $this->fromTick,
            'to_tick' => (int) $this->toTick,
            'title' => $title ?: ucfirst((string) $this->type) . ' Chronicle',
            'summary' => $summary,
            'content' => $this->content,
            'type' => WorldOsResourceSupport::chronicleType($this->type),
            'importance' => (float) $this->importance,
            'actor_id' => $this->actorId,
            'world_event_id' => $this->worldEventId,
            'has_animation' => ! empty($this->rawPayload['animation_script'] ?? null),
            'animation_script' => $this->when(
                $request->routeIs('worldos.chronicles.show'),
                $this->rawPayload['animation_script'] ?? null
            ),
        ];
    }
}
