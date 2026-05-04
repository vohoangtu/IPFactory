<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use App\Modules\Simulation\Services\Core\RuleMutationService;
use Illuminate\Support\Facades\Config;

$dslPath = 'simulation/integrity';
$mutationService = app(RuleMutationService::class);
$ruleVm = app(RuleVmService::class);

echo "--- Autopoiesis Verification (Inside Container) ---\n";

// 1. Check Original
Config::set('worldos.autopoiesis.enabled', false);
$original = $ruleVm->resolveDslContent($dslPath);
echo "Original Content Length: " . strlen($original) . "\n";

if (empty($original)) {
    echo "ERROR: Original DSL is empty. Check path: resources/worldos_rules/simulation/integrity.dsl\n";
}

// 2. Apply Mutation
$testContent = $original . "\n# TEST_MUTATION_MARKER";
$mutationService->applyMutation($dslPath, $testContent, ['test' => true]);
echo "Mutation Applied.\n";

// 3. Verify Mutation Resolved (Enabled)
Config::set('worldos.autopoiesis.enabled', true);

// Clear cache to force reload
$ref = new ReflectionProperty(RuleVmService::class, 'dslFileCache');
$ref->setAccessible(true);
$ref->setValue(null, []);

$resolvedEnabled = $ruleVm->resolveDslContent($dslPath);
$hasMarker = str_contains($resolvedEnabled, '# TEST_MUTATION_MARKER');
echo "Resolved (Enabled) has marker: " . ($hasMarker ? 'YES' : 'NO') . "\n";

// 4. Verify Mutation Ignored (Disabled)
Config::set('worldos.autopoiesis.enabled', false);
// Clear static cache in RuleVmService via reflection
$ref = new ReflectionProperty(RuleVmService::class, 'dslFileCache');
$ref->setAccessible(true);
$ref->setValue(null, []);

$resolvedDisabled = $ruleVm->resolveDslContent($dslPath);
$hasMarkerDisabled = str_contains($resolvedDisabled, '# TEST_MUTATION_MARKER');
echo "Resolved (Disabled) has marker: " . ($hasMarkerDisabled ? 'YES' : 'NO') . "\n";

// Cleanup
$mutationService->rollbackMutation($dslPath);
echo "Rollback Applied.\n";
echo "--------------------------------------------------\n";
