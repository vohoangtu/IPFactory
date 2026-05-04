<?php

namespace Tests\Unit\Simulation;

use App\Contracts\SimulationEngineClientInterface;
use App\Modules\Simulation\Core\Runtime\RuleVM\EffectExecutor;
use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use App\Modules\Simulation\Services\Cosmology\AxiomRegistry;
use App\Modules\Simulation\Services\Core\RuleMutationService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RuleVmDslLoadingTest extends TestCase
{
    private const DSL_PATH = 'tests/autopoiesis_loader';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        RuleVmService::clearDslCache();
    }

    protected function tearDown(): void
    {
        RuleVmService::clearDslCache();

        $fullPath = $this->dslFilePath();
        if (File::exists($fullPath)) {
            File::delete($fullPath);
        }

        $dir = dirname($fullPath);
        if (File::isDirectory($dir) && count(File::files($dir)) === 0) {
            File::deleteDirectory($dir);
        }

        parent::tearDown();
    }

    public function test_rule_vm_loads_mutated_dsl_when_autopoiesis_is_enabled(): void
    {
        File::ensureDirectoryExists(dirname($this->dslFilePath()));
        File::put($this->dslFilePath(), "rule entropy > 0.8 => emit_event baseline");

        config()->set('worldos.autopoiesis.enabled', true);

        app(RuleMutationService::class)->applyMutation(
            self::DSL_PATH,
            "rule entropy > 0.7 => emit_event mutated",
            ['vector' => 'test'],
        );

        $dsl = $this->makeService()->resolveDslContent(self::DSL_PATH);

        $this->assertSame("rule entropy > 0.7 => emit_event mutated", trim($dsl));
    }

    public function test_rule_vm_falls_back_to_original_dsl_when_autopoiesis_is_disabled(): void
    {
        File::ensureDirectoryExists(dirname($this->dslFilePath()));
        File::put($this->dslFilePath(), "rule entropy > 0.8 => emit_event baseline");

        app(RuleMutationService::class)->applyMutation(
            self::DSL_PATH,
            "rule entropy > 0.7 => emit_event mutated",
            ['vector' => 'test'],
        );

        config()->set('worldos.autopoiesis.enabled', false);
        RuleVmService::clearDslCache(self::DSL_PATH);

        $dsl = $this->makeService()->resolveDslContent(self::DSL_PATH);

        $this->assertSame("rule entropy > 0.8 => emit_event baseline", trim($dsl));
    }

    public function test_rule_vm_cache_is_flushed_after_mutation_is_applied(): void
    {
        File::ensureDirectoryExists(dirname($this->dslFilePath()));
        File::put($this->dslFilePath(), "rule entropy > 0.8 => emit_event baseline");

        config()->set('worldos.autopoiesis.enabled', true);

        $service = $this->makeService();
        $this->assertSame("rule entropy > 0.8 => emit_event baseline", trim($service->resolveDslContent(self::DSL_PATH)));

        app(RuleMutationService::class)->applyMutation(
            self::DSL_PATH,
            "rule entropy > 0.7 => emit_event mutated",
            ['vector' => 'cache_refresh'],
        );

        $this->assertSame("rule entropy > 0.7 => emit_event mutated", trim($service->resolveDslContent(self::DSL_PATH)));
    }

    private function makeService(): RuleVmService
    {
        return new RuleVmService(
            $this->fakeEngineClient(),
            app(AxiomRegistry::class),
            app(EffectExecutor::class),
            app(RuleMutationService::class),
        );
    }

    private function fakeEngineClient(): SimulationEngineClientInterface
    {
        return new class implements SimulationEngineClientInterface
        {
            public function advance(int $universeId, int $ticks, array $stateInput = [], ?array $worldConfig = null): array
            {
                return ['ok' => true, 'snapshot' => null];
            }

            public function merge(string $stateA, string $stateB): array
            {
                return ['ok' => true, 'snapshot' => null];
            }

            public function batchAdvance(array $requests): array
            {
                return ['responses' => []];
            }

            public function analyzeTrajectory(array $points, float $threshold = 0.1): array
            {
                return [
                    'is_strange_attractor' => false,
                    'is_bounded' => true,
                    'recurrence_rate' => 0.0,
                    'max_lyapunov_estimate' => 0.0,
                    'trajectory_variance' => 0.0,
                    'basin_center' => [],
                    'basin_radius' => 0.0,
                    'regime_transitions' => [],
                ];
            }

            public function evaluateRules(array $state, ?string $rulesDsl = null): array
            {
                return ['ok' => true, 'outputs' => []];
            }

            public function processActorsSoa(
                int $tick,
                array $ids,
                array $zoneIds,
                array $hunger,
                array $energy,
                array $fear,
                array $trauma,
                array $heroicTypes,
                array $lineageIds,
                array $memes,
                array $traitsMatrix,
                array $behaviorStates = [],
                array $behaviorGraphs = [],
                array $archetypes = [],
                array $socialGraph = [],
                array $edicts = [],
                array $factionIds = [],
                array $factionLoyalty = [],
                bool $isObserved = false,
                array $narrativeContext = [],
                array $factionRelations = [],
                array $beliefDefinitions = [],
                array $beliefAlignments = [],
                array $techDefinitions = [],
                array $actorTechLevels = []
            ): array {
                return ['ok' => true, 'outputs' => [], 'scars' => [], 'spawned_actors' => []];
            }

            public function processFieldsV7(
                array $fields,
                array $neighborCounts,
                array $neighborOffsets,
                array $neighbors,
                float $diffusionRate,
                float $preservationRate
            ): array {
                return ['ok' => true, 'fields' => []];
            }

            public function computeMetabolismGrid(
                array $populations,
                array $biomasses,
                array $industries,
                float $efficiency,
                float $baseEnergy
            ): array {
                return ['ok' => true, 'grid' => []];
            }

            public function calculateVocationAlignment(array $actorMotivation, array $targetProfile): float
            {
                return 0.0;
            }

            public function getCombinedGravity(array $rulesets): float
            {
                return 0.0;
            }
        };
    }

    private function dslFilePath(): string
    {
        return resource_path('worldos_rules/tests/autopoiesis_loader.dsl');
    }
}
