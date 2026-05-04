<?php

namespace App\Modules\WorldOS\Http\Resources;

use App\Modules\Intelligence\Models\Actor;
use App\Modules\Intelligence\Models\AgentDecision;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Actor */
class ActorSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->loadMissing('supremeEntity');
        $metrics = is_array($this->metrics) ? $this->metrics : [];
        $stats = is_array($this->stats) ? $this->stats : [];
        $lastDecision = AgentDecision::query()
            ->where('actor_id', $this->id)
            ->orderByDesc('tick')
            ->first();

        return [
            'id' => $this->id,
            'universe_id' => (int) $this->universe_id,
            'name' => $this->name,
            'role' => $this->archetype ?: 'Unknown role',
            'archetype' => $this->archetype,
            'influence' => (float) ($metrics['influence'] ?? $stats['influence'] ?? $this->supremeEntity?->power_level ?? 0),
            'alignment' => $this->resolveAlignment(),
            'last_decision' => $lastDecision?->reasoning ?: 'No recent decision recorded.',
            'is_alive' => (bool) $this->is_alive,
            'life_stage' => $this->life_stage,
            'birth_tick' => (int) ($this->birth_tick ?? 0),
            'death_tick' => $this->death_tick,
        ];
    }

    private function resolveAlignment(): string
    {
        $traits = is_array($this->traits) ? $this->traits : [];

        $dominantTrait = collect($traits)
            ->map(static function ($value, $key) {
                return is_numeric($value) ? ['trait' => (string) $key, 'score' => (float) $value] : null;
            })
            ->filter()
            ->sortByDesc('score')
            ->first();

        if ($dominantTrait) {
            return ucfirst(str_replace('_', ' ', $dominantTrait['trait']));
        }

        return $this->is_alive ? 'Adaptive' : 'Dormant';
    }
}
