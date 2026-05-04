<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Repositories\UniverseRepository;
use App\Modules\Simulation\Core\Support\SimulationRandom;
use Illuminate\Support\Facades\DB;

/**
 * Processes attractor_instances: decay, spawn from rules; merges live instances into state_vector active_attractors.
 */
final class DynamicAttractorEngine
{
    public function __construct(
        protected UniverseRepository $universeRepository
    ) {}

    public function process(Universe $universe, UniverseSnapshot $snapshot, SimulationRandom $rng): void
    {
        $currentTick = (int) $snapshot->tick;
        $universeId = $universe->id;

        $instances = DB::table('attractor_instances')
            ->where('universe_id', $universeId)
            ->get();

        $decayByType = $this->getDecayRates();

        foreach ($instances as $instance) {
            $decay = (float) ($decayByType[$instance->attractor_type] ?? 0.02);
            $strength = (float) $instance->strength - $decay;
            if ($strength <= 0) {
                DB::table('attractor_instances')->where('id', $instance->id)->delete();
                continue;
            }
            DB::table('attractor_instances')->where('id', $instance->id)->update(['strength' => $strength]);
        }

        $instances = DB::table('attractor_instances')
            ->where('universe_id', $universeId)
            ->get();

        foreach ($instances as $instance) {
            $this->trySpawnChild($instance, $currentTick, $universeId, $rng);
        }

        $this->mergeActiveAttractors($universe);
    }

    private function getDecayRates(): array
    {
        $rows = DB::table('civilization_attractors')->get();
        $out = [];
        foreach ($rows as $r) {
            $out[$r->name] = (float) $r->decay_rate;
        }
        return $out;
    }

    private function trySpawnChild(object $instance, int $currentTick, int $universeId, SimulationRandom $rng): void
    {
        $rules = DB::table('attractor_spawn_rules')
            ->where('parent_type', $instance->attractor_type)
            ->get();

        foreach ($rules as $rule) {
            if ($rng->float(0, 1) > (float) $rule->probability) {
                continue;
            }
            DB::table('attractor_instances')->insert([
                'universe_id' => $universeId,
                'attractor_type' => $rule->child_type,
                'strength' => 1.0,
                'state_json' => null,
                'spawned_by' => $instance->id,
                'created_tick' => $currentTick,
                'expires_tick' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function mergeActiveAttractors(Universe $universe): void
    {
        $vec = $universe->state_vector ?? [];
        if (!is_array($vec)) {
            $vec = [];
        }
        $configAttractors = DB::table('civilization_attractors')->get()->keyBy('name');

        $instances = DB::table('attractor_instances')
            ->where('universe_id', $universe->id)
            ->get();

        $fromInstances = [];
        foreach ($instances as $inst) {
            $cfg = $configAttractors->get($inst->attractor_type);
            $forceMap = $cfg && $cfg->force_map
                ? (is_string($cfg->force_map) ? json_decode($cfg->force_map, true) : $cfg->force_map)
                : [];
            $fromInstances[] = [
                'type' => $inst->attractor_type,
                'strength' => (float) $inst->strength,
                'force_map' => is_array($forceMap) ? $forceMap : [],
            ];
        }

        $existing = $vec['active_attractors'] ?? [];
        if (!is_array($existing)) {
            $existing = [];
        }
        $vec['active_attractors'] = array_merge($existing, $fromInstances);
        $this->universeRepository->update($universe->id, ['state_vector' => $vec]);
    }
}

