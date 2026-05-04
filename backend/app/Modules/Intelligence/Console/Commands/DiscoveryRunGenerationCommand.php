<?php

namespace App\Modules\Intelligence\Console\Commands;

use App\Modules\Simulation\Services\Politics\CivilizationDiscoveryService;
use Illuminate\Console\Command;

/**
 * Run one Civilization Discovery GA generation (Phase 3 §3.3).
 * Evaluates fitness, selects top-k, optionally crossover+mutate; outputs evaluated, selected, next_generation.
 */
class DiscoveryRunGenerationCommand extends Command
{
    protected $signature = 'worldos:discovery-run-generation
                            {--ids= : Comma-separated universe IDs to evaluate (e.g. 1,2,3)}
                            {--json : Output result as JSON only}';

    protected $description = 'Run one GA generation: evaluate fitness, selection (top-k), optional crossover+mutate.';

    public function handle(CivilizationDiscoveryService $discovery): int
    {
        $idsOption = $this->option('ids');
        $universeIds = [];
        if (is_string($idsOption) && $idsOption !== '') {
            $universeIds = array_map('intval', array_filter(array_map('trim', explode(',', $idsOption))));
        }
        if (empty($universeIds)) {
            $this->warn('No universe IDs provided. Use --ids=1,2,3 or configure worldos.civilization_discovery.ga_universe_ids.');
            $configured = config('worldos.civilization_discovery.ga_universe_ids', []);
            if (is_array($configured) && ! empty($configured)) {
                $universeIds = array_values(array_map('intval', $configured));
                $this->info('Using configured ga_universe_ids: ' . implode(', ', $universeIds));
            } else {
                return self::FAILURE;
            }
        }

        $result = $discovery->runGeneration($universeIds);

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return self::SUCCESS;
        }

        $this->info('Evaluated (universe_id => fitness):');
        foreach ($result['evaluated'] as $id => $fitness) {
            $this->line("  {$id} => {$fitness}");
        }
        $this->info('Selected (top-k): ' . implode(', ', $result['selected']));
        $this->info('Next generation (IDs for next run): ' . implode(', ', $result['next_generation']));
        return self::SUCCESS;
    }
}
