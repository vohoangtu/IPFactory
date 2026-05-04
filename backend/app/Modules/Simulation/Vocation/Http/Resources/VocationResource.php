<?php

namespace App\Modules\Simulation\Vocation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VocationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'tier' => $this->tier,
            'element_affinity' => $this->elementAffinity,
            'skills' => SkillResource::collection($this->whenLoaded('skills') ?: []),
            'evolves_to' => $this->evolvesTo,
            'metadata' => $this->metadata,
        ];
    }
}
