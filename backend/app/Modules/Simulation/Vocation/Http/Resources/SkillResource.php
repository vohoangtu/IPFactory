<?php

namespace App\Modules\Simulation\Vocation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SkillResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'element' => $this->element,
            'cost' => $this->cost,
            'rule_dsl' => $this->ruleDsl,
            'metadata' => $this->metadata,
        ];
    }
}
