<?php

require __DIR__ . '/backend/vendor/autoload.php';
$app = require_once __DIR__ . '/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use App\Modules\Simulation\Services\RuleMutationService;
use Illuminate\Support\Facades\Config;

$dslPath = 'simulation/integrity';
$fullPath = resource_path('worldos_rules/' . $dslPath . '.dsl');
$mutationService = app(RuleMutationService::class);
$ruleVm = app(RuleVmService::class);

echo "--- Autopoiesis Verification ---\n";

// 1. Check Original
Config::set('worldos.autopoiesis.enabled', false);
$original = $ruleVm->resolveDslContent($dslPath);
echo "Original Content Length: " . strlen($original) . "\n";

// 2. Apply Mutation
$testContent = $original . "\n# TEST_MUTATION_MARKER";
$mutationService->applyMutation($dslPath, $testContent, ['test' => true]);
echo "Mutation Applied.\n";

// 3. Verify Mutation Resolved (Enabled)
Config::set('worldos.autopoiesis.enabled', true);
$resolvedEnabled = $ruleVm->resolveDslContent($dslPath);
$hasMarker = str_contains($resolvedEnabled, '# TEST_MUTATION_MARKER');
echo "Resolved (Enabled) has marker: " . ($hasMarker ? 'YES' : 'NO') . "\n";

// 4. Verify Mutation Ignored (Disabled)
Config::set('worldos.autopoiesis.enabled', false);
// Clear cache first as it's static in RuleVmService
$ref = new ReflectionProperty(RuleVmService::class, 'dslFileCache');
$ref->setAccessible(true);
$ref->setValue(null, []);

$resolvedDisabled = $ruleVm->resolveDslContent($dslPath);
$hasMarkerDisabled = str_contains($resolvedDisabled, '# TEST_MUTATION_MARKER');
echo "Resolved (Disabled) has marker: " . ($hasMarkerDisabled ? 'YES' : 'NO') . "\n";

// Cleanup
$mutationService->rollbackMutation($dslPath);
echo "Rollback Applied.\n";
echo "-------------------------------\n";
