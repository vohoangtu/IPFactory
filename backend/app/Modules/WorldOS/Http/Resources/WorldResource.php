<?php

namespace App\Modules\WorldOS\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorldResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'origin' => $this->origin,
            'axiom' => $this->axiom ?? [],
            'current_genre' => $this->current_genre,
            'base_genre' => $this->base_genre,
            'is_autonomic' => (bool) ($this->is_autonomic ?? false),
        ];
    }
}
