<?php

namespace App\Modules\Simulation\Console\Commands;

use App\Modules\World\Models\Universe;
use App\Modules\World\Models\World;
use App\Modules\Simulation\Services\Narrative\CivilizationMemoryEngine;
use App\Modules\Simulation\Services\Core\GreatPersonEngine;
use App\Modules\Simulation\Services\Culture\IdeologyEvolutionEngine;
use App\Modules\Simulation\Services\Culture\MythologyGeneratorEngine;
use App\Modules\Simulation\Services\Narrative\NarrativeExtractionEngine;
use App\Modules\Simulation\Services\Meta\TimelineSelectionEngine;
use Illuminate\Console\Command;

/**
 * Phase I: Single CLI entry point for WorldOS engines (Timeline Selection, Narrative Extraction,
 * Civilization Memory, Mythology, Ideology Evolution, Great Person).
 */
class WorldosEnginesCommand extends Command
{
    protected $signature = 'worldos:engines
        {action : timeline-selection | extract-lore | civilization-memory | mythology | ideology | great-person}
        {--world= : World ID (for timeline-selection, extract-lore)}
        {--saga= : Saga ID (for timeline-selection, extract-lore)}
        {--universe= : Universe ID (for civilization-memory, mythology, ideology, great-person)}
        {--limit= : Limit (e.g. extract-lore limit, timeline-selection limit)}
        {--tick= : Tick (for great-person spawn)}';

    protected $description = 'Run WorldOS engines: timeline-selection, extract-lore, civilization-memory, mythology, ideology, great-person';

    public function handle(
        TimelineSelectionEngine $timelineSelection,
        NarrativeExtractionEngine $narrativeExtraction,
        CivilizationMemoryEngine $civMemory,
        MythologyGeneratorEngine $mythology,
        IdeologyEvolutionEngine $ideology,
        GreatPersonEngine $greatPerson
    ): int {
        $action = $this->argument('action');
        $worldId = $this->option('world') ? (int) $this->option('world') : null;
        $sagaId = $this->option('saga') ? (int) $this->option('saga') : null;
        $universeId = $this->option('universe') ? (int) $this->option('universe') : null;
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $tick = $this->option('tick') !== null ? (int) $this->option('tick') : null;

        switch ($action) {
            case 'timeline-selection':
                return $this->runTimelineSelection($timelineSelection, $worldId, $sagaId, $limit);
            case 'extract-lore':
                return $this->runExtractLore($narrativeExtraction, $worldId, $sagaId, $limit);
            case 'civilization-memory':
                return $this->runCivilizationMemory($civMemory, $universeId);
            case 'mythology':
                return $this->runMythology($mythology, $universeId);
            case 'ideology':
                return $this->runIdeology($ideology, $universeId);
            case 'great-person':
                return $this->runGreatPerson($greatPerson, $universeId, $tick);
            default:
                $this->error("Unknown action: {$action}. Use timeline-selection | extract-lore | civilization-memory | mythology | ideology | great-person");
                return 1;
        }
    }

    private function runTimelineSelection(TimelineSelectionEngine $engine, ?int $worldId, ?int $sagaId, ?int $limit): int
    {
        if ($worldId) {
            $world = World::find($worldId);
            if (! $world) {
                $this->error("World {$worldId} not found.");
                return 1;
            }
            $universes = $engine->selectBest($world, $limit);
        } elseif ($sagaId) {
            $saga = Saga::find($sagaId);
            if (! $saga) {
                $this->error("Saga {$sagaId} not found.");
                return 1;
            }
            $universes = $engine->selectBestForSaga($saga, $limit);
        } else {
            $this->error("Provide --world= or --saga= for timeline-selection.");
            return 1;
        }
        $this->table(['Universe ID', 'Name'], $universes->map(fn ($u) => [$u->id, $u->name ?? ''])->toArray());
        $this->info("Selected {$universes->count()} timeline(s).");
        return 0;
    }

    private function runExtractLore(NarrativeExtractionEngine $engine, ?int $worldId, ?int $sagaId, ?int $limit): int
    {
        if ($worldId) {
            $world = World::find($worldId);
            if (! $world) {
                $this->error("World {$worldId} not found.");
                return 1;
            }
            $chronicles = $engine->extractBestFromWorld($world, $limit);
        } elseif ($sagaId) {
            $saga = Saga::find($sagaId);
            if (! $saga) {
                $this->error("Saga {$sagaId} not found.");
                return 1;
            }
            $chronicles = $engine->extractBestFromSaga($saga, $limit);
        } else {
            $this->error("Provide --world= or --saga= for extract-lore.");
            return 1;
        }
        $this->info("Extracted {$chronicles->count()} lore chronicle(s).");
        foreach ($chronicles as $c) {
            $this->line("  Chronicle #{$c->id} (universe {$c->universe_id}, ticks {$c->from_tick}-{$c->to_tick})");
        }
        return 0;
    }

    private function runCivilizationMemory(CivilizationMemoryEngine $engine, ?int $universeId): int
    {
        $universe = $this->resolveUniverse($universeId);
        if (! $universe) {
            return 1;
        }
        $memory = $engine->getMemory($universe);
        $this->info("Universe {$universe->id}: {$memory['from_tick']}-{$memory['to_tick']}, key_events=" . count($memory['key_events']) . ", collapse_hints=" . count($memory['collapse_hints']));
        $this->line(json_encode($memory, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return 0;
    }

    private function runMythology(MythologyGeneratorEngine $engine, ?int $universeId): int
    {
        $universe = $this->resolveUniverse($universeId);
        if (! $universe) {
            return 1;
        }
        $chronicle = $engine->generateFromUniverse($universe);
        if ($chronicle) {
            $this->info("Mythology chronicle #{$chronicle->id} created for universe {$universe->id}.");
        } else {
            $this->warn("No mythology chronicle created (check logs).");
        }
        return 0;
    }

    private function runIdeology(IdeologyEvolutionEngine $engine, ?int $universeId): int
    {
        $universe = $this->resolveUniverse($universeId);
        if (! $universe) {
            return 1;
        }
        $result = $engine->getDominantIdeology($universe);
        $this->info("Universe {$universe->id}: institution_count={$result['institution_count']}, dominant_ideology:");
        $this->line(json_encode($result['dominant'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return 0;
    }

    private function runGreatPerson(GreatPersonEngine $engine, ?int $universeId, ?int $tick): int
    {
        $universe = $this->resolveUniverse($universeId);
        if (! $universe) {
            return 1;
        }
        $tick = $tick ?? (int) ($universe->current_tick ?? 0);
        $eval = $engine->evaluateCandidates($universe, $tick);
        $this->info("Eval: eligible=" . ($eval['eligible'] ? 'yes' : 'no') . ", reason={$eval['reason']}, entropy={$eval['entropy']}, institutions={$eval['institution_count']}");
        if ($eval['eligible']) {
            $entity = $engine->spawnIfEligible($universe, $tick);
            if ($entity) {
                $this->info("Spawned SupremeEntity #{$entity->id}: {$entity->name}");
            }
        }
        return 0;
    }

    private function resolveUniverse(?int $universeId): ?Universe
    {
        if ($universeId === null) {
            $this->error("Provide --universe= for this action.");
            return null;
        }
        $universe = Universe::find($universeId);
        if (! $universe) {
            $this->error("Universe {$universeId} not found.");
            return null;
        }
        return $universe;
    }
}

