<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Simulation\Core\EngineProductMapping;
use App\Modules\Simulation\Core\EngineRegistry;
use App\Modules\Simulation\Services\Core\SimulationMetricsExporter;
use App\Modules\Simulation\Services\Core\UniverseStateSummaryBuilder;
use App\Modules\Simulation\Services\Culture\IdeologyEvolutionEngine;
use App\Modules\Simulation\Services\Culture\MythologyGeneratorEngine;
use App\Modules\Simulation\Services\Meta\TimelineSelectionEngine;
use App\Modules\Simulation\Services\Narrative\CivilizationMemoryEngine;
use App\Modules\Simulation\Services\Narrative\NarrativeExtractionEngine;
use App\Modules\World\Models\Universe;
use App\Modules\World\Models\World;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorldosEnginesController extends Controller
{
    public function worldTimelines(
        string $id,
        TimelineSelectionEngine $engine,
        Request $request
    ): JsonResponse {
        $world = World::find((int) $id);
        if (! $world) {
            return response()->json(['message' => 'World not found'], 404);
        }
        $limit = $request->query('limit') !== null ? (int) $request->query('limit') : null;
        $universes = $engine->selectBest($world, $limit);

        return response()->json([
            'world_id' => $world->id,
            'timelines' => $universes->map(fn ($u) => ['id' => $u->id, 'name' => $u->name ?? ''])->values(),
        ]);
    }

    public function worldExtractLore(
        string $id,
        NarrativeExtractionEngine $engine,
        Request $request
    ): JsonResponse {
        $world = World::find((int) $id);
        if (! $world) {
            return response()->json(['message' => 'World not found'], 404);
        }
        $limit = $request->input('limit') ?? $request->query('limit');
        $limit = $limit !== null ? (int) $limit : null;
        $chronicles = $engine->extractBestFromWorld($world, $limit);

        return response()->json([
            'world_id' => $world->id,
            'chronicles' => $chronicles->map(fn ($c) => [
                'id' => $c->id,
                'universe_id' => $c->universe_id,
                'from_tick' => $c->from_tick,
                'to_tick' => $c->to_tick,
                'type' => $c->type,
            ])->values(),
        ]);
    }

    public function civilizationMemory(
        string $id,
        CivilizationMemoryEngine $engine,
        Request $request
    ): JsonResponse {
        $universe = Universe::find((int) $id);
        if (! $universe) {
            return response()->json(['message' => 'Universe not found'], 404);
        }
        $fromTick = $request->query('from_tick') !== null ? (int) $request->query('from_tick') : null;
        $toTick = $request->query('to_tick') !== null ? (int) $request->query('to_tick') : null;
        $memory = $engine->getMemory($universe, $fromTick, $toTick);

        return response()->json($memory);
    }

    public function mythology(
        string $id,
        MythologyGeneratorEngine $engine,
        Request $request
    ): JsonResponse {
        $universe = Universe::find((int) $id);
        if (! $universe) {
            return response()->json(['message' => 'Universe not found'], 404);
        }
        $fromTick = $request->input('from_tick') ?? $request->query('from_tick');
        $toTick = $request->input('to_tick') ?? $request->query('to_tick');
        $fromTick = $fromTick !== null ? (int) $fromTick : null;
        $toTick = $toTick !== null ? (int) $toTick : null;
        $chronicle = $engine->generateFromUniverse($universe, $fromTick, $toTick);
        if (! $chronicle) {
            return response()->json(['message' => 'Mythology generation failed'], 500);
        }

        return response()->json([
            'chronicle_id' => $chronicle->id,
            'universe_id' => $universe->id,
            'from_tick' => $chronicle->from_tick,
            'to_tick' => $chronicle->to_tick,
        ]);
    }

    public function ideology(string $id, IdeologyEvolutionEngine $engine): JsonResponse
    {
        $universe = Universe::find((int) $id);
        if (! $universe) {
            return response()->json(['message' => 'Universe not found'], 404);
        }
        $result = $engine->getDominantIdeology($universe);

        return response()->json([
            'universe_id' => $universe->id,
            'dominant' => $result['dominant'],
            'institution_count' => $result['institution_count'],
            'previous_dominant' => $result['previous_dominant'],
        ]);
    }

    public function index(EngineRegistry $registry, EngineProductMapping $mapping): JsonResponse
    {
        return response()->json([
            'engines' => $mapping->getEnginesWithProducts($registry),
            'product_to_engines' => $mapping->getProductToEngines($registry),
        ]);
    }

    public function status(): JsonResponse
    {
        $engines = [];
        $classes = [
            'TimelineSelectionEngine' => TimelineSelectionEngine::class,
            'NarrativeExtractionEngine' => NarrativeExtractionEngine::class,
            'CivilizationMemoryEngine' => CivilizationMemoryEngine::class,
            'MythologyGeneratorEngine' => MythologyGeneratorEngine::class,
            'IdeologyEvolutionEngine' => IdeologyEvolutionEngine::class,
            'GreatPersonEngine' => \App\Modules\Simulation\Services\Core\GreatPersonEngine::class,
        ];
        foreach ($classes as $name => $class) {
            try {
                app($class);
                $engines[$name] = true;
            } catch (\Throwable $e) {
                $engines[$name] = false;
            }
        }

        $config = [
            'scheduler.tick_budget' => config('worldos.scheduler.tick_budget'),
            'timeline_selection.default_limit' => config('worldos.timeline_selection.default_limit'),
            'narrative_extraction.default_limit' => config('worldos.narrative_extraction.default_limit'),
            'autonomic.fork_entropy_min' => config('worldos.autonomic.fork_entropy_min'),
            'pulse.run_ideology' => config('worldos.pulse.run_ideology'),
            'pulse.run_great_person' => config('worldos.pulse.run_great_person'),
        ];

        return response()->json([
            'ok' => ! in_array(false, $engines, true),
            'engines' => $engines,
            'config' => $config,
        ]);
    }

    public function metrics(SimulationMetricsExporter $exporter): \Illuminate\Http\Response
    {
        return response($exporter->toPrometheusText(), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function stateSummary(string $id, UniverseStateSummaryBuilder $builder): JsonResponse
    {
        $universe = Universe::find((int) $id);
        if (! $universe) {
            return response()->json(['message' => 'Universe not found'], 404);
        }

        return response()->json($builder->build($universe));
    }
}
