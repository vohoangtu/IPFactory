<?php

$categories = [
    'Cosmology' => [
        'CosmicEnergyPoolService', 'CosmicPhaseDetector', 'CosmogenesisService', 'HeatDeathService', 
        'OriginSeeder', 'RealityCalibrationService', 'SamsaraService', 'SimulationClock', 'VaultService',
        'EpochEngine', 'AxiomRegistry', 'AxiomaticUniverseCreator'
    ],
    'Ecology' => [
        'AnomalyGeneratorService', 'DemographicRatesService', 'DemographicStages', 'EcosystemMetricsService',
        'EvolutionarySparkService', 'EvolutionPressureService', 'GenomeAdaptationService', 'GeographyResourceService',
        'KernelMutationService', 'PressureCalculator', 'SimulationPRNG', 'SurvivalPruningService', 'ZenithMetricsService',
        'UrbanStressAgricultureService'
    ],
    'Society' => [
        'ActorCognitiveService', 'AutonomicEvolutionEngine', 'AutonomicWorkerService', 'DemographicRatesService',
        'InstitutionDecayService', 'LegitimacyEliteService', 'SocialGraphService', 'VocationActionEngine', 'VocationEngine',
        'SoulAnchorService', 'TheDreamingService'
    ],
    'Culture' => [
        'GenreEvolutionService', 'IdeologyConversionService', 'IdeologyEvolutionEngine', 'MythologyGeneratorEngine',
        'ResonanceAuditorService', 'ResonanceEngine'
    ],
    'Meta' => [
        'ConvergenceEngine', 'ConvergenceScoreService', 'MultiverseInteractionService', 'MultiverseSchedulerEngine',
        'MultiverseSovereigntyService', 'MultiverseSynthesisService', 'ParadoxResolver', 'TemporalSyncService',
        'TimelineSelectionEngine', 'TrajectoryModelingEngine', 'UniverseRuntimeService', 'VoidExplorationEngine',
        'WorldRegulatorEngine', 'WorldSimulationStatusService', 'WorldTemplateManager'
    ],
    'Narrative' => [
        'CivilizationMemoryEngine', 'CivilizationNarrativeInterpreter', 'GrandNarrativeService', 'NarrativeExtractionEngine',
        'NarrativeFeedbackService', 'SagaBuilderService', 'ProphecyEngine'
    ],
    'Core' => [
        'AdaptiveSchedulerService', 'CausalCacheService', 'CausalCorrectionEngine', 'CheatGranterService',
        'CollapsePropagatonService', 'EventNormalizer', 'ExternalStorageManager', 'FfiActorEngine', 'FieldDiffusionEngine',
        'GreatPersonEngine', 'GreatPersonLegacyService', 'GrpcSimulationEngineClient', 'HeroLifecycleService',
        'HolographicCompressionService', 'HttpSimulationEngineClient', 'ImplicitOrchestratorService', 'InnovationRateService',
        'KnowledgeGraphService', 'MacroAgentSpawnService', 'MetaEdictService', 'MetricsExtractor',
        'NullCausalityGraphService', 'NullUniverseSimilarityService', 'ObservationInterferenceEngine', 'ObserverService',
        'ObserverSpectrumService', 'ReasoningService', 'RedisCausalityGraphService', 'RuleMutationService',
        'SelfImprovingSimulationService', 'SimulationMetricsExporter', 'SimulationMetricsLogger', 'SimulationTracer',
        'StateVectorUniverseSimilarityService', 'StructuralHashService', 'StubSimulationEngineClient'
    ],
    'Politics' => [
         'CivilizationDiscoveryService'
    ]
];

$oldNamespace = 'App\Modules\Simulation\Services';
$movedMap = [];

foreach ($categories as $domain => $classes) {
    $newNamespace = "App\\Modules\\Simulation\\Services\\{$domain}";
    foreach ($classes as $class) {
        $oldFqcn = "$oldNamespace\\$class";
        $newFqcn = "$newNamespace\\$class";
        $movedMap[$oldFqcn] = $newFqcn;
    }
}

$dirsToProcess = [
    __DIR__ . '/tests',
    __DIR__ . '/database',
    __DIR__ . '/routes',
    __DIR__ . '/app', // we'll process app again just in case some services reference others across different modules
];

function processDirectory($dir, $movedMap) {
    if (!is_dir($dir)) return;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isDir() || $file->getExtension() !== 'php') {
            continue;
        }
        
        $path = $file->getPathname();
        $content = file_get_contents($path);
        if ($content === false) continue;
        
        $original = $content;
        
        foreach ($movedMap as $oldFqcn => $newFqcn) {
            $content = str_replace("use $oldFqcn;", "use $newFqcn;", $content);
            $content = str_replace("\\$oldFqcn::", "\\$newFqcn::", $content);
        }
        
        if ($content !== $original) {
            file_put_contents($path, $content);
            echo "Updated references in {$file->getPathname()}\n";
        }
    }
}

foreach ($dirsToProcess as $dir) {
    processDirectory($dir, $movedMap);
}

// Process root PHP files
$rootFiles = glob(__DIR__ . '/*.php');
foreach ($rootFiles as $path) {
    $content = file_get_contents($path);
    if ($content === false) continue;
    $original = $content;
    foreach ($movedMap as $oldFqcn => $newFqcn) {
        $content = str_replace("use $oldFqcn;", "use $newFqcn;", $content);
        $content = str_replace("\\$oldFqcn::", "\\$newFqcn::", $content);
    }
    if ($content !== $original) {
        file_put_contents($path, $content);
        echo "Updated references in $path\n";
    }
}

echo "Namespace fix completed.\n";
