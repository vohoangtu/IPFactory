<?php

namespace App\Modules\Simulation\Services\Meta;

use App\Modules\World\Models\Universe;
use App\Modules\World\Models\World;

class WorldTemplateManager
{
    /**
     * Apply a local axiom shift to a specific universe.
     * This modifies the universe's parameters without affecting the parent world.
     */
    public function applyLocalAxiomShift(Universe $universe, array $axiomShift): void
    {
        $metadata = $universe->metadata ?? [];
        $shifts = $metadata['axiom_shifts'] ?? [];
        
        $shifts[] = [
            'shift' => $axiomShift,
            'applied_at_tick' => $universe->current_tick,
            'timestamp' => now()->toIso8601String()
        ];
        
        $metadata['axiom_shifts'] = $shifts;
        $universe->metadata = $metadata;
        $universe->save();
        
        // Cần tích hợp với Rust engine để truyền axiom_shifts vào mô phỏng
        // Hiện tại lưu ở dạng metadata để Engine đọc khi init
    }

    /**
     * Apply a global axiom shift to the entire world.
     * This affects all new universes spawned from this world.
     */
    public function applyGlobalAxiomShift(World $world, array $axiomShift): void
    {
        $config = $world->simulation_config ?? [];
        $shifts = $config['global_axiom_shifts'] ?? [];
        
        $shifts[] = [
            'shift' => $axiomShift,
            'applied_at' => now()->toIso8601String()
        ];
        
        $config['global_axiom_shifts'] = $shifts;
        $world->simulation_config = $config;
        $world->save();
    }
}


