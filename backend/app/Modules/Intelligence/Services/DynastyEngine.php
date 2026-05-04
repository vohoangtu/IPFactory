<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\World\Models\Universe;
use App\Modules\Intelligence\Entities\ActorEntity;
use App\Modules\Intelligence\Contracts\ActorRepositoryInterface;
use Illuminate\Support\Facades\Log;

/**
 * Phase 52: Dynasty & Lineage Engine 🩸🏰
 * 
 * Quản lý sự kế thừa huyết thống, tố chất tâm lý và vết sẹo lịch sử.
 */
class DynastyEngine
{
    protected const INHERITANCE_FACTOR = 0.4;
    protected const DESTINY_GRADIENT_BOOST = 0.2; // Boost cho con của Vĩ nhân

    public function __construct(
        protected ActorRepositoryInterface $actorRepository
    ) {}

    /**
     * Xử lý sự kế thừa cho các Actor mới sinh.
     * (Trong mô phỏng memory, chúng ta có thể gọi này khi một Actor mới được tạo ra từ cha mẹ)
     */
    public function inherit(ActorEntity $child, ActorEntity $parent): void
    {
        $child->traits = $this->calculateInheritedTraits($child->traits, $parent->traits, $parent->isHeroic);
        
        // Gán lineage_id từ cha
        $child->metrics['lineage_id'] = $parent->metrics['lineage_id'] ?? "Dynasty_" . $parent->id;
        $child->metrics['generation'] = ($parent->metrics['generation'] ?? 1) + 1;

        // Áp dụng Historical Scars
        $this->applyHistoricalScars($child, $parent);

        Log::info("DYNASTY: {$child->name} (Gen {$child->metrics['generation']}) has inherited the legacy of {$parent->name}.");
    }

    /**
     * Tính toán tố chất kế thừa: mix traits của cha mẹ với một phần ngẫu nhiên.
     */
    protected function calculateInheritedTraits(array $childTraits, array $parentTraits, bool $isParentHeroic): array
    {
        $alpha = self::INHERITANCE_FACTOR;
        if ($isParentHeroic) {
            $alpha += self::DESTINY_GRADIENT_BOOST;
        }

        $inherited = [];
        foreach (ActorEntity::TRAIT_DIMENSIONS as $trait) {
            $pVal = $parentTraits[$trait] ?? 0.5;
            $cVal = $childTraits[$trait] ?? 0.5;
            
            // Công thức: (Alpha * Parent) + ((1 - Alpha) * Child's original random)
            $inherited[$trait] = round(($alpha * $pVal) + ((1 - $alpha) * $cVal), 3);
        }

        return $inherited;
    }

    /**
     * Áp dụng các "Vết sẹo" hoặc "Hào quang" dựa trên quá khứ của cha mẹ.
     */
    protected function applyHistoricalScars(ActorEntity $child, ActorEntity $parent): void
    {
        // Nếu cha là Vĩ nhân -> Hậu duệ có "Hào quang vương tộc" (Authority boost)
        if ($parent->isHeroic) {
            $child->traits['Dominance'] = min(1.0, $child->traits['Dominance'] + 0.15);
            $child->metrics['historical_scars'][] = [
                'type' => 'noble_blood',
                'description' => "Descendant of the legendary {$parent->name}",
                'impact' => 0.2
            ];
        }

        // Nếu cha tử trận (Grief/Vengeance)
        if (!$parent->isAlive && str_contains($parent->biography ?? '', 'fallen')) {
            $child->traits['Vengeance'] = min(1.0, ($child->traits['Vengeance'] ?? 0.1) + 0.3);
            $child->metrics['historical_scars'][] = [
                'type' => 'blood_feud',
                'description' => "Seeking justice for the fall of {$parent->name}",
                'impact' => 0.1
            ];
        }
    }

    /**
     * Tìm kiếm hậu duệ tiềm năng để "đánh thức" tố chất vĩ nhân.
     */
    public function evaluateAwakening(ActorEntity $actor): bool
    {
        $legacyScore = $actor->metrics['legacy_score'] ?? 0;
        $generation = $actor->metrics['generation'] ?? 1;

        // Dòng dõi càng lâu đời + di sản cha ông càng lớn -> xác suất Awakening càng cao
        $awakeningChance = ($legacyScore * 0.1) + ($generation * 0.02);
        
        return rand(0, 1000) / 1000.0 < $awakeningChance;
    }
}

