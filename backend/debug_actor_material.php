<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Core\Supervisor\SimulationSupervisor;
use App\Modules\Intelligence\Models\Actor;

$universeId = 2; // Universe test
$universe = Universe::find($universeId);

if (!$universe) {
    die("Universe $universeId not found\n");
}

echo "--- BEFORE TICK ---\n";
echo "Universe Tick: " . ($universe->tick ?? $universe->state_vector['tick'] ?? 'N/A') . "\n";
$actorCount = \App\Modules\Intelligence\Models\Actor::where('universe_id', $universeId)->count();
$aliveCount = \App\Modules\Intelligence\Models\Actor::where('universe_id', $universeId)->where('is_alive', true)->count();
echo "Total Actors: $actorCount, Alive: $aliveCount\n";

$zones = $universe->state_vector['zones'] ?? [];
if (!empty($zones)) {
    echo "Zone 0 Materials: " . json_encode($zones[0]['state']['available_materials'] ?? []) . "\n";
}

echo "\nAdvancing 1 tick...\n";
$supervisor = app(SimulationSupervisor::class);
$supervisor->execute($universeId, 1);

$universe->refresh();
echo "\n--- AFTER TICK ---\n";
echo "Universe Tick: " . ($universe->tick ?? $universe->state_vector['tick'] ?? 'N/A') . "\n";
$aliveCountAfter = \App\Modules\Intelligence\Models\Actor::where('universe_id', $universeId)->where('is_alive', true)->count();
echo "Alive Actors: $aliveCountAfter\n";

$zonesAfter = $universe->state_vector['zones'] ?? [];
if (!empty($zonesAfter)) {
    echo "Zone 0 Materials: " . json_encode($zonesAfter[0]['state']['available_materials'] ?? []) . "\n";
}
