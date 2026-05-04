<?php

$basePath = __DIR__ . '/app/Modules/Simulation/Services';
$appPath = __DIR__ . '/app';

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
        'KnowledgeGraphService', 'MacroAgentSpawn सर्विस -> MacroAgentSpawnService', 'MetaEdictService', 'MetricsExtractor',
        'NullCausalityGraphService', 'NullUniverseSimilarityService', 'ObservationInterferenceEngine', 'ObserverService',
        'ObserverSpectrumService', 'ReasoningService', 'RedisCausalityGraphService', 'RuleMutationService',
        'SelfImprovingSimulationService', 'SimulationMetricsExporter', 'SimulationMetricsLogger', 'SimulationTracer',
        'StateVectorUniverseSimilarityService', 'StructuralHashService', 'StubSimulationEngineClient', 'MacroAgentSpawnService'
    ],
    'Politics' => [
         'CivilizationDiscoveryService'
    ]
];

// Flatten mapping
$mapping = [];
foreach ($categories as $domain => $classes) {
    if (!is_dir("$basePath/$domain")) {
        mkdir("$basePath/$domain", 0755, true);
    }
    foreach ($classes as $class) {
        $mapping[$class] = $domain;
    }
}

// 1. Move files and update their internal namespace
echo "Moving files...\n";
$movedMap = []; // Old FQCN => New FQCN
$files = glob("$basePath/*.php");
foreach ($files as $file) {
    $className = basename($file, '.php');
    if (!isset($mapping[$className])) {
        // Leave unknown files in root Services
        continue;
    }
    
    $domain = $mapping[$className];
    $newFile = "$basePath/$domain/$className.php";
    
    $content = file_get_contents($file);
    
    // Update basic namespace
    $oldNamespace = 'App\Modules\Simulation\Services';
    $newNamespace = "App\Modules\Simulation\Services\\{$domain}";
    
    $content = str_replace("namespace $oldNamespace;", "namespace $newNamespace;", $content);
    
    file_put_contents($newFile, $content);
    unlink($file);
    
    $oldFqcn = "$oldNamespace\\$className";
    $newFqcn = "$newNamespace\\$className";
    $movedMap[$oldFqcn] = $newFqcn;
    
    echo "Moved $className to $domain\n";
}

// 2. Global Search and Replace in /app for references
echo "\nUpdating references across app/ ...\n";

function processDirectory($dir, $movedMap) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isDir() || $file->getExtension() !== 'php') {
            continue;
        }
        
        $path = $file->getPathname();
        $content = file_get_contents($path);
        $original = $content;
        
        foreach ($movedMap as $oldFqcn => $newFqcn) {
            // Replace use statements: use App\Modules\Simulation\Services\X; -> use App\Modules\Simulation\Services\Domain\X;
            $content = str_replace("use $oldFqcn;", "use $newFqcn;", $content);
            // Replace inline standard accesses like \App\Modules\Simulation\Services\X
            $content = str_replace("\\$oldFqcn", "\\$newFqcn", $content);
        }
        
        if ($content !== $original) {
            file_put_contents($path, $content);
            // echo "Updated references in {$file->getFilename()}\n";
        }
    }
}

processDirectory($appPath, $movedMap);

echo "\nDone!\n";
