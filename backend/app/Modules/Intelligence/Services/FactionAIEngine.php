<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\World\Models\Universe;
use App\Modules\Intelligence\Contracts\ActorRepositoryInterface;
use App\Modules\Intelligence\Entities\ActorEntity;
use App\Modules\Simulation\Services\Ecology\SimulationPRNG;
use Illuminate\Support\Facades\Log;

/**
 * Phase 24: Faction Collective AI.
 * Handles recruitment, ideology alignment, and collective decision biases.
 */
class FactionAIEngine
{
    public function __construct(
        protected ActorRepositoryInterface $actorRepository
    ) {}

    /**
     * Tự động chiêu mộ thành viên cho các phe phái.
     */
    public function recruit(Universe $universe, array &$factions, array $actors, SimulationPRNG $rng): void
    {
        if (empty($factions) || empty($actors)) return;

        foreach ($factions as &$faction) {
            $memberIds = $faction['member_actor_ids'] ?? [];
            $type = $faction['type'] ?? 'generic';
            
            foreach ($actors as $actor) {
                if (in_array($actor->id, $memberIds)) continue;

                // Heuristic chiêu mộ dựa trên Archetype
                $isCompatible = $this->checkCompatibility($actor, $type, $rng);

                if ($isCompatible) {
                    $memberIds[] = $actor->id;
                    Log::info("FACTION: Actor #{$actor->id} joined Faction {$faction['name']} ({$type})");
                }
            }
            
            // Giới hạn quy mô phe phái ban đầu (để tránh 1 phe nuốt cả thế giới quá nhanh)
            $faction['member_actor_ids'] = array_slice($memberIds, 0, 50);
            
            // Cập nhật hệ tư tưởng phe phái
            $faction['ideology_vector'] = $this->calculateIdeology($faction, $actors);
        }
    }

    /**
     * Kiểm tra mức độ phù hợp của Actor với loại phe phái.
     */
    protected function checkCompatibility(ActorEntity $actor, string $type, SimulationPRNG $rng): bool
    {
        $archetype = $actor->archetype;

        $chance = 0.05; // Base chance 5% mỗi tick

        switch ($type) {
            case 'militaristic':
                if (in_array($archetype, ['Chiến Binh', 'Hộ Vệ', 'Lãnh Đạo'])) $chance = 0.35;
                break;
            case 'academic':
                if (in_array($archetype, ['Học Giả', 'Kỹ Sư', 'Kẻ Phá Bĩnh'])) $chance = 0.35;
                break;
            case 'insurgent':
                if (in_array($archetype, ['Tà Tu', 'Kẻ Phá Bĩnh'])) $chance = 0.3;
                if ($actor->traits['Vengeance'] ?? 0 > 0.7) $chance += 0.2;
                break;
            case 'religious':
                if (in_array($archetype, ['Tu Sĩ', 'Tín Đồ'])) $chance = 0.4;
                break;
        }

        return $rng->nextFloat() < $chance;
    }

    /**
     * Mỗi phe phái đóng vai trò là một "máy phát" tạo ra áp lực lên các trường hấp dẫn.
     */
    public function generateFieldPressure(array $factions, float $globalEntropy): array
    {
        $deltas = [];

        foreach ($factions as $faction) {
            $type = $faction['type'] ?? 'generic';
            $memberCount = count($faction['member_actor_ids'] ?? []);
            if ($memberCount === 0) continue;

            $strength = log10($memberCount + 10) * 0.05;

            $impact = [
                'survival' => 0.0,
                'reproduction' => 0.0,
                'wealth' => 0.0,
                'power' => 0.0,
                'knowledge' => 0.0,
                'meaning' => 0.0,
                'status' => 0.0,
                'belonging' => 0.0,
                'entropy_delta' => 0.0
            ];

            switch ($type) {
                case 'militaristic':
                    $impact['power'] = $strength * 2.0;
                    $impact['status'] = $strength * 1.5;
                    $impact['survival'] = $strength * 0.5;
                    $impact['entropy_delta'] = 0.01;
                    break;
                case 'academic':
                    $impact['knowledge'] = $strength * 2.5;
                    $impact['wealth'] = $strength * 0.5;
                    $impact['status'] = $strength * 0.8;
                    break;
                case 'insurgent':
                    $impact['power'] = $strength * 1.5;
                    $impact['entropy_delta'] = 0.04;
                    break;
                case 'religious':
                    $impact['meaning'] = $strength * 2.0;
                    $impact['belonging'] = $strength * 1.5;
                    $impact['entropy_delta'] = -0.02;
                    break;
                case 'mercantile':
                    $impact['wealth'] = $strength * 2.0;
                    $impact['status'] = $strength * 1.0;
                    break;
            }

            $deltas[$faction['id']] = $impact;
        }

        return $deltas;
    }

    /**
     * Tính toán Ideology Vector trung bình của các thành viên.
     */
    protected function calculateIdeology(array $faction, array $actors): array
    {
        $memberIds = $faction['member_actor_ids'] ?? [];
        if (empty($memberIds)) return [0.5, 0.5, 0.5]; // Neutral default

        $sumDominance = 0;
        $sumSolidarity = 0;
        $sumCuriosity = 0;
        $count = 0;

        foreach ($actors as $actor) {
            if (in_array($actor->id, $memberIds)) {
                $sumDominance += (float)($actor->traits['Dominance'] ?? 0.5);
                $sumSolidarity += (float)($actor->traits['Solidarity'] ?? 0.5);
                $sumCuriosity += (float)($actor->traits['Curiosity'] ?? 0.5);
                $count++;
            }
        }

        if ($count === 0) return [0.5, 0.5, 0.5];

        return [
            $sumDominance / $count,
            $sumSolidarity / $count,
            $sumCuriosity / $count
        ];
    }
}


