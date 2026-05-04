<?php

namespace App\Modules\WorldOS\Http\Resources;

use App\Modules\WorldOS\Http\Resources\Support\WorldOsResourceSupport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'universe_id' => (int) $this->id,
            'name' => $this->name ?: "Branch {$this->id}",
            'label' => $this->name ?: "Branch {$this->id}",
            'status' => WorldOsResourceSupport::normalizeBranchStatus($this->status),
            'divergence_tick' => (int) ($this->forked_at_tick ?? 0),
            'forked_at_tick' => (int) ($this->forked_at_tick ?? 0),
            'current_tick' => (int) ($this->current_tick ?? 0),
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
