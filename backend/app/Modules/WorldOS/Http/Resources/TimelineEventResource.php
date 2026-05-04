<?php

namespace App\Modules\WorldOS\Http\Resources;

use App\Modules\WorldOS\Http\Resources\Support\WorldOsResourceSupport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimelineEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $facts = WorldOsResourceSupport::decodeList($this->facts ?? []);
        $category = (string) ($this->category ?? $this->type ?? 'transition');
        $zone = (string) ($this->zone_id ?? $this->zone ?? 'Global');

        return [
            'id' => $this->id ?? "timeline-{$this->tick}",
            'tick' => (int) ($this->tick ?? 0),
            'year' => isset($this->year) ? (int) $this->year : null,
            'category' => $category,
            'zone' => $zone,
            'summary' => (string) ($this->summary ?? $facts[0] ?? $this->content ?? "{$category} event recorded in {$zone}."),
            'actors' => WorldOsResourceSupport::decodeList($this->actors ?? []),
            'institutions' => WorldOsResourceSupport::decodeList($this->institutions ?? []),
            'facts' => $facts,
        ];
    }
}
