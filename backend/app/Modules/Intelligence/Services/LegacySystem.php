<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\World\Models\Universe;
use App\Modules\Intelligence\Entities\ActorState;
use Illuminate\Support\Facades\Log;

/**
 * Phase 44.3: Legacy System (Di sản Lịch sử)
 * 
 * Chuyển hóa tầm ảnh hưởng của vĩ nhân sau khi qua đời thành 
 * các giá trị bền vững cho văn minh (Floors).
 */
class LegacySystem
{
    public const PRESERVATION_RATE = 0.4; // Di sản bị mài mòn, chỉ giữ lại 40% tầm vóc gốc

    /**
     * Ghi nhận di sản của một vĩ nhân khi họ qua đời.
     */
    public function imprintLegacy(Universe $universe, ActorState $actor): void
    {
        if (!$actor->isHeroic || !$actor->heroicType) {
            return;
        }

        $stateVector = $universe->state_vector;
        $legacy = $stateVector['legacy'] ?? [];
        if (!is_array($legacy)) {
            $legacy = [
                'knowledge_floor' => 0.0,
                'stability_floor' => 0.0,
                'culture_floor'   => 0.0,
                'institutional_legacy' => [],
                'memetic_imprint' => []
            ];
        }

        $type = $actor->heroicType;
        Log::info("LEGACY: Actor #{$actor->id} ({$type}) has left a legacy for Universe #{$universe->id}");

        // Institutional & Knowledge Legacy
        switch ($type) {
            case 'SCIENTIST':
                $legacy['knowledge_floor'] = max($legacy['knowledge_floor'] ?? 0.0, 0.3);
                break;
            case 'RULER':
            case 'GENERAL':
                $legacy['stability_floor'] = max($legacy['stability_floor'] ?? 0.0, 0.4);
                break;
            case 'PROPHET':
            case 'ARTIST':
                $legacy['culture_floor'] = max($legacy['culture_floor'] ?? 0.0, 0.35);
                break;
        }

        // Phase 47: 8D Memetic Imprint
        $actorCulture = $actor->metrics['culture'] ?? [];
        if (!isset($legacy['memetic_imprint']) || !is_array($legacy['memetic_imprint'])) {
            $legacy['memetic_imprint'] = [];
        }
        
        foreach ($actorCulture as $meme => $val) {
            $legacy['memetic_imprint'][$meme] = max($legacy['memetic_imprint'][$meme] ?? 0.0, $val * 0.5);
        }

        // Tích hợp vào Historical Scars
        $scars = $stateVector['historical_scars'] ?? [];
        $scars[] = [
            'tick' => $universe->current_tick ?? 0,
            'type' => 'LEGACY_IMPRINT',
            'actor_name' => $actor->name,
            'description' => "Di sản của {$actor->name} đã trở thành nền tảng cho văn minh."
        ];

        $stateVector['legacy'] = $legacy;
        $stateVector['historical_scars'] = $scars;
        $universe->state_vector = $stateVector;
        
        if ($universe->id !== 1) {
            $universe->save();
        }
    }

    /**
     * Ghi nhận di sản từ một định chế khi nó sụp đổ.
     */
    public function imprintInstitutionalLegacy(Universe $universe, array $institution): void
    {
        $stateVector = $universe->state_vector;
        $legacy = $stateVector['legacy'] ?? [];
        if (!is_array($legacy)) {
            $legacy = [];
        }
        
        $impact = $institution['impact_vector'] ?? [];
        $stability = $institution['stability'] ?? 0.5;
        
        if (!isset($legacy['institutional_legacy']) || !is_array($legacy['institutional_legacy'])) {
            $legacy['institutional_legacy'] = [];
        }

        foreach ($impact as $field => $val) {
            $legacy['institutional_legacy'][$field] = max($legacy['institutional_legacy'][$field] ?? 0.0, $val * $stability * 0.4);
        }

        $scars = $stateVector['historical_scars'] ?? [];
        $scars[] = [
            'tick' => $universe->current_tick ?? 0,
            'type' => 'INSTITUTION_LEGACY',
            'name' => $institution['name'],
            'description' => "Định chế {$institution['name']} sụp đổ nhưng đã để lại cấu trúc di sản cho {$universe->name}."
        ];

        $stateVector['legacy'] = $legacy;
        $stateVector['historical_scars'] = $scars;
        $universe->state_vector = $stateVector;
    }

    public function applyFloors(array &$fields, array $legacy): void
    {
        $rate = $legacy['preservation_rate'] ?? self::PRESERVATION_RATE;

        // 1. Core Legacy Floors
        if (isset($legacy['knowledge_floor'])) {
            $fields['knowledge'] = max($fields['knowledge'], $legacy['knowledge_floor'] * $rate);
        }
        if (isset($legacy['stability_floor'])) {
             $fields['power'] = max($fields['power'] ?? 0, $legacy['stability_floor'] * $rate);
             $fields['survival'] = max($fields['survival'] ?? 0, $legacy['stability_floor'] * $rate * 0.5);
        }
        if (isset($legacy['culture_floor'])) {
            $fields['meaning'] = max($fields['meaning'] ?? 0, $legacy['culture_floor'] * $rate);
        }

        // 2. Institutional Legacy (8-dimensional)
        $instLegacy = $legacy['institutional_legacy'] ?? [];
        foreach ($instLegacy as $field => $val) {
            if (isset($fields[$field])) {
                $fields[$field] = max($fields[$field], $val * $rate);
            }
        }

        // 3. Memetic Imprint (8-dimensional culture)
        $memetic = $legacy['memetic_imprint'] ?? [];
        foreach ($memetic as $meme => $val) {
            // Map memes to fields if applicable, or influence culture directly
            // In this phase, we map them directly to field baselines for resonance
            $field = $this->mapMemeToField($meme);
            if ($field && isset($fields[$field])) {
                $fields[$field] = max($fields[$field], $val * $rate * 0.5);
            }
        }
    }

    private function mapMemeToField(string $meme): ?string
    {
        return match($meme) {
            'survival_grit' => 'survival',
            'reproductive_norms' => 'reproduction',
            'mercantile_ethic' => 'wealth',
            'violence_tolerance' => 'power',
            'innovation_openness' => 'knowledge',
            'ritual_rigidity' => 'meaning',
            'aesthetic_value' => 'status',
            'collectivism_index' => 'belonging',
            default => null
        };
    }
}

