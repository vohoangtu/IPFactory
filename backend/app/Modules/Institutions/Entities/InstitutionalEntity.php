<?php

namespace App\Modules\Institutions\Entities;

class InstitutionalEntity
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $universeId,
        public string $name,
        public string $entityType,
        public array $ideologyVector = [],
        public array $influenceMap = [],
        public float $orgCapacity = 15.0,
        public float $institutionalMemory = 0.5,
        public float $legitimacy = 0.5,
        public ?int $spawnedAtTick = null,
        public ?int $collapsedAtTick = null
    ) {}

    /**
     * Cập nhật ảnh hưởng và năng lực tổ chức.
     * Logic dựa trên InstitutionManager legacy.
     */
    public function tick(array $zones, float $capacityMultiplier = 1.0, float $growthMultiplier = 1.0): void
    {
        if ($this->isCollapsed()) return;

        $totalSupport = 0.0;
        $newInfluenceMap = [];

        foreach ($zones as $zone) {
            $zoneId = $zone['id'];
            $currentInfluence = $this->influenceMap[$zoneId] ?? 0.0;
            
            // Legitimacy check: alignment between ideology and culture
            $alignment = $this->calculateAlignment($this->ideologyVector, $zone['culture'] ?? []);
            
            if ($alignment > 0.4) {
                $growth = 0.02 * $alignment * $growthMultiplier;
            } else {
                $growth = -0.01 * (1.0 - $alignment);
            }

            $newInfluenceMap[$zoneId] = max(0.0, min(1.0, $currentInfluence + $growth));
            $totalSupport += $newInfluenceMap[$zoneId];
        }

        // Maintenance cost based on institutional memory and scale
        $maintenance = (count($zones) * 0.03) * (1.1 - $this->institutionalMemory);
        
        $change = ($totalSupport * 0.15 * $capacityMultiplier) - $maintenance;
        $this->orgCapacity = max(0.0, $this->orgCapacity + $change);
        $this->influenceMap = $newInfluenceMap;
        
        // Memory decay
        $this->institutionalMemory = max(0.1, $this->institutionalMemory * 0.999);
    }

    public function isCollapsed(): bool
    {
        return $this->orgCapacity <= 0 || $this->collapsedAtTick !== null;
    }

    public function collapse(int $tick): void
    {
        $this->collapsedAtTick = $tick;
        $this->orgCapacity = 0;
    }

    private function calculateAlignment(array $ideology, array $culture): float
    {
        if (empty($ideology) || empty($culture)) return 0.5;
        
        $diff = 0.0;
        $count = 0;
        foreach ($ideology as $key => $val) {
            if (isset($culture[$key])) {
                $diff += abs($val - $culture[$key]);
                $count++;
            }
        }
        
        if ($count === 0) return 0.5;
        return 1.0 - ($diff / $count);
    }
}
