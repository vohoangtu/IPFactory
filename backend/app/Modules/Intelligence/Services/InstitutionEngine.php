<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\World\Models\Universe;
use App\Modules\Intelligence\Entities\ActorState;
use Illuminate\Support\Facades\Log;

/**
 * Phase 46: Institution Engine (Khung xương & Sự mục ruỗng) 🏛️
 * 
 * Quản lý vòng đời và tác động của các định chế xã hội.
 * Cơ chế mới: Decay, Corruption, Competition và Spawn từ Vĩ nhân.
 */
class InstitutionEngine
{
    public const LIFECYCLE = [
        'BIRTH'     => 'Hình thành',
        'GROWTH'    => 'Phát triển',
        'DOMINANCE' => 'Thống trị',
        'DECAY'     => 'Suy thoái',
        'COLLAPSE'  => 'Sụp đổ'
    ];

    protected LegacySystem $legacySystem;

    public function __construct(LegacySystem $legacySystem)
    {
        $this->legacySystem = $legacySystem;
    }

    /**
     * Cập nhật trạng thái của các định chế trong Universe.
     */
    public function step(Universe $universe): void
    {
        $stateVector = $universe->state_vector;
        $institutions = $stateVector['institutions'] ?? [];

        foreach ($institutions as $id => &$inst) {
            if (!is_array($inst)) continue;
            $prevState = $inst['state'] ?? 'unknown';
            $this->evolveLifecycle($inst, $universe);
            
            // Phase 47: Check transition to COLLAPSE to trigger Legacy
            if ($inst['state'] === 'COLLAPSE' && $prevState !== 'COLLAPSE') {
                $this->legacySystem->imprintInstitutionalLegacy($universe, $inst);
            }

            $this->applyInternalFriction($inst, $institutions);
        }

        $stateVector['institutions'] = $institutions;
        $universe->state_vector = $stateVector;
    }

    /**
     * Nảy sinh định chế từ sự kết tinh của một Trường phái tư tưởng.
     */
    public function spawnFromSchool(Universe $universe, array $school): void
    {
        $theme = $school['idea_theme'] ?? 'unknown';
        $instName = "Học thuyết " . ($school['name'] ?? "Vô danh");
        
        $impact = match($theme) {
            'rationalism'  => ['knowledge' => 0.3, 'power' => 0.1],
            'spirituality' => ['meaning' => 0.4, 'belonging' => 0.2],
            'expansionism' => ['power' => 0.4, 'wealth' => 0.2],
            'order'        => ['power' => 0.3, 'survival' => 0.3],
            'mercantilism' => ['wealth' => 0.5, 'power' => 0.1],
            'humanism'     => ['status' => 0.3, 'knowledge' => 0.2],
            default        => ['status' => 0.1]
        };

        $stateVector = $universe->state_vector;
        $institutions = $stateVector['institutions'] ?? [];
        
        $newId = "inst_sch_" . bin2hex(random_bytes(3));
        $institutions[$newId] = [
            'id' => $newId,
            'name' => $instName,
            'state' => 'BIRTH',
            'stability' => 1.2, // Cao hơn định chế cá nhân vì có nền tảng tư tưởng
            'corruption' => 0.0,
            'impact_vector' => $impact,
            'founded_at' => $universe->current_tick ?? 0,
            'school_origin' => $school['id']
        ];

        $stateVector['institutions'] = $institutions;
        $universe->state_vector = $stateVector;
        
        Log::info("COGNITIVE INSTITUTION: {$instName} has crystallized from ideological foundation.");
    }

    public function spawnFromHero(Universe $universe, ActorState $actor): void
    {
        $type = $actor->heroicType;
        $instName = match($type) {
            'SCIENTIST' => "Viện Hàn Lâm " . $actor->name,
            'GENERAL', 'RULER' => "Hệ thống Hành chính/Quân sự " . $actor->name,
            'PROPHET'   => "Giáo hội " . $actor->name,
            'ARTIST'    => "Phường hội Nghệ thuật " . $actor->name,
            default     => "Tổ chức " . $actor->name
        };

        $impact = match($type) {
            'SCIENTIST' => ['knowledge' => 0.2, 'status' => 0.05],
            'GENERAL'   => ['power' => 0.25, 'survival' => 0.1],
            'PROPHET'   => ['meaning' => 0.3, 'belonging' => 0.15],
            default     => ['status' => 0.1]
        };

        $stateVector = $universe->state_vector;
        $institutions = $stateVector['institutions'] ?? [];
        
        $newId = bin2hex(random_bytes(4));
        $institutions[$newId] = [
            'id' => $newId,
            'name' => $instName,
            'state' => 'BIRTH',
            'stability' => 1.0,
            'corruption' => 0.0,
            'impact_vector' => $impact,
            'founded_at' => $universe->current_tick ?? 0,
            'hero_origin' => $actor->id
        ];

        $stateVector['institutions'] = $institutions;
        $universe->state_vector = $stateVector;
        
        Log::info("INSTITUTION SPAWN: {$instName} has been founded in Universe #{$universe->id}");
    }

    private function evolveLifecycle(array &$inst, Universe $universe): void
    {
        $age = ($universe->current_tick ?? 0) - ($inst['founded_at'] ?? 0);
        $stability = $inst['stability'] ?? 1.0;
        $corruption = $inst['corruption'] ?? 0.0;
        $fields = $universe->state_vector['fields'] ?? [];

        // Phase 46: Corruption Bloom (Wealth > 0.7 and Meaning < 0.3 accelerated corruption)
        $corruptionGrowth = 0.002;
        if (($fields['wealth'] ?? 0) > 0.7 && ($fields['meaning'] ?? 0) < 0.3) {
            $corruptionGrowth *= 3.0; // Materialism without purpose breeds corruption
        }
        $inst['corruption'] = min(1.0, $corruption + $corruptionGrowth);
        
        // Phase 46: Rigidity factor - older institutions become "hard" and brittle
        $rigidity = min(0.5, $age / 2000.0);
        
        // Rigidity: High age makes it harder to adapt (simulated via higher decay)
        $decayRate = 0.005 + ($inst['corruption'] * 0.02) + $rigidity;

        // Life Stages
        if ($age > 400 + (int)($stability * 100)) {
            $inst['state'] = 'DECAY';
            $inst['stability'] = max(0.0, $stability - ($decayRate * 2));
        } elseif ($age > 150) {
            $inst['state'] = 'DOMINANCE';
            $inst['stability'] = max(0.5, $stability - $decayRate);
        } elseif ($age > 50) {
            $inst['state'] = 'GROWTH';
            $inst['stability'] = min(1.2, $stability + 0.005);
        }

        // Collapse check
        if ($inst['stability'] <= 0.15 || $inst['corruption'] > 0.9) {
            $inst['state'] = 'COLLAPSE';
        }
    }

    private function applyInternalFriction(array &$inst, array $all): void
    {
        // Phase 46: Advanced Competition logic - Friction between same primary impact type
        $myPrimary = $this->getPrimaryImpactField($inst);
        
        $competitors = 0;
        foreach ($all as $other) {
            if ($other['id'] === $inst['id'] || $other['state'] === 'COLLAPSE') continue;
            if ($this->getPrimaryImpactField($other) === $myPrimary) {
                $competitors++;
            }
        }

        if ($competitors > 0) {
            // Friction drains stability based on number of direct competitors
            $inst['stability'] -= 0.002 * $competitors;
        }

        // Generic systemic overhead
        if (count($all) > 5) {
            $inst['stability'] -= 0.001 * (count($all) - 5);
        }
    }

    private function getPrimaryImpactField(array $inst): string
    {
        $impact = $inst['impact_vector'] ?? [];
        if (empty($impact)) return 'none';
        arsort($impact);
        return array_key_first($impact);
    }

    /**
     * Tổng hợp tác động của các định chế lên các trường lực.
     * Sử dụng mô hình cộng dồn có giới hạn (Diminishing Returns).
     */
    public function getInstitutionalModifiers(array $institutions): array
    {
        $sums = [];
        foreach ($institutions as $inst) {
            if ($inst['state'] === 'COLLAPSE') continue;

            $impact = $inst['impact_vector'] ?? [];
            $efficiency = ($inst['stability'] ?? 1.0) * (1.0 - ($inst['corruption'] ?? 0.0));
            
            foreach ($impact as $field => $val) {
                $sums[$field] = ($sums[$field] ?? 0.0) + ($val * $efficiency);
            }
        }

        $modifiers = [];
        foreach ($sums as $field => $totalImpact) {
            // Chuyển đổi sum thành hệ số nhân: 1.0 + ln(1 + sum)
            // Điều này đảm bảo dù có 10 định chế thì modifier cũng không tăng vô hạn
            $modifiers[$field] = 1.0 + log(1.0 + $totalImpact);
        }

        return $modifiers;
    }
}

