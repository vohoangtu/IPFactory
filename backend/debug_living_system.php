<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Intelligence\Entities\ActorEntity;

$universeId = $argv[1] ?? 1;

$universe = Universe::find($universeId);
if (!$universe) {
    die("Universe $universeId not found\n");
}

$snapshot = UniverseSnapshot::where('universe_id', $universeId)
    ->orderBy('tick', 'desc')
    ->first();

if (!$snapshot) {
    die("No snapshot found for Universe $universeId\n");
}

$state = $snapshot->state_vector;

echo "--- UNIVERSE STATE TICK: {$snapshot->tick} ---\n";
echo "Stability Index: " . ($state['stability_index'] ?? 'N/A') . "\n";
echo "Global Entropy: " . ($state['fields']['entropy'] ?? $state['entropy'] ?? 'N/A') . "\n";
echo "Total Population: " . ($state['total_population'] ?? 'N/A') . "\n";

echo "\n--- SAMPLE AGENTS (from JSON) ---\n";
if (isset($state['agents']) && is_array($state['agents'])) {
    foreach ($state['agents'] as $agent) {
        echo "ID: {$agent['id']} | Name: {$agent['name']} | Hunger: {$agent['hunger']} | Energy: {$agent['energy']} | Zone: {$agent['zone_id']}\n";
    }
} else {
    echo "NO AGENTS IN JSON STATE VECTOR\n";
}

echo "\n--- ZONE RESOURCES ---\n";
if (isset($state['zones']) && is_array($state['zones'])) {
    foreach ($state['zones'] as $zone) {
        $res = $zone['state']['resources'] ?? $zone['state']['resource'] ?? $zone['state']['food'] ?? 0;
        echo "Zone " . ($zone['id'] ?? '?') . ": Resources: " . round($res, 2) . " | Pop Scalar: " . ($zone['state']['population'] ?? 0) . "\n";
    }
}
