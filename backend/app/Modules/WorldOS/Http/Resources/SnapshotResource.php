<?php

namespace App\Modules\WorldOS\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SnapshotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'universe_id' => (int) $this->universe_id,
            'tick' => (int) $this->tick,
            'label' => "Snapshot {$this->tick}",
            'created_at' => optional($this->created_at)->toISOString(),
            'summary' => $this->getSummary(),
            'note' => $this->getSummary(),
            'entropy' => (float) ($this->entropy ?? 0),
            'stability_index' => (float) ($this->stability_index ?? 0),
            'metrics' => $this->metrics ?? [],
        ];
    }
}
