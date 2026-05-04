<?php

namespace App\Modules\Intelligence\Actions;

use App\Contracts\ActionInterface;
use App\Modules\Intelligence\Contracts\ActorRepositoryInterface;
use App\Modules\Intelligence\Entities\ActorEntity;

class SpawnActorAction implements ActionInterface
{
    public function __construct(
        private ActorRepositoryInterface $actorRepository
    ) {}

    public function execute(mixed ...$args): mixed
    {
        return $this->doExecute($args[0]);
    }

    public function doExecute(array $data): ActorEntity
    {
        $metrics = $data['metrics'] ?? ['influence' => 0.5];
        if (array_key_exists('spawned_at_tick', $data)) {
            $metrics['spawned_at_tick'] = $data['spawned_at_tick'];
        }
        if (!isset($metrics['physic'])) {
            $metrics['physic'] = ActorEntity::defaultPhysicVector();
        }
        $traits = $data['traits'] ?? $this->generateDefaultTraits();
        $defaultMaxAgeYears = max(1, (int) config('worldos.intelligence.default_max_age_years', 150));
        $metrics = ActorEntity::ensureLifeExpectancyInMetrics($metrics, $traits, $defaultMaxAgeYears);
        $energyMax = (float) config('worldos.intelligence.energy_max_default', 200);
        if (!array_key_exists('energy', $metrics)) {
            $metrics['energy'] = $energyMax;
            $metrics['max_energy'] = $energyMax;
        }
        if (!isset($metrics['metabolism'])) {
            $physic = $metrics['physic'] ?? null;
            $base = (float) config('worldos.intelligence.metabolism_base', 0.5);
            $agg = 0.5;
            if ($physic && is_array($physic)) {
                $v = array_values(array_filter($physic, 'is_numeric'));
                $agg = $v ? array_sum($v) / count($v) : 0.5;
            }
            $metrics['metabolism'] = $base * (0.6 + 0.2 * $agg);
        }

        $era = strtolower($data['era'] ?? 'genesis');
        $name = $data['name'];
        $archetype = $data['archetype'];
        $biography = $data['biography'] ?? null;

        // Era-Specific Archetype and Biography Adjustment
        if ($era === 'paleolithic') {
            if ($archetype === 'Leader') {
                $archetype = 'Alpha';
                $name = "Tù trưởng " . $name;
                $biography = $biography ?: "Một thợ săn dũng cảm vươn lên dẫn dắt bộ lạc bằng sức mạnh và sự khôn ngoan nguyên thủy.";
            }
        } elseif ($era === 'cyberpunk') {
            if ($archetype === 'Leader') {
                $archetype = 'CEO';
                $name = "Giám đốc " . $name;
                $biography = $biography ?: "Một kiến trúc sư dữ liệu nắm giữ quyền lực thông qua mạng lưới thông tin và vốn hóa thị trường.";
            }
        }

        $actor = new ActorEntity(
            id: null,
            universeId: $data['universe_id'],
            name: $name,
            archetype: $archetype,
            traits: $traits,
            metrics: $metrics,
            isAlive: true,
            generation: $data['generation'] ?? 1,
            biography: $biography
        );

        $this->actorRepository->save($actor);

        return $actor;
    }

    private function generateDefaultTraits(): array
    {
        // 18 dimensions: 17 gốc + Longevity (index 17)
        return array_fill(0, 18, 0.5);
    }
}
