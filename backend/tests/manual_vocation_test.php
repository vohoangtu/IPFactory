<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$actor = new \App\Modules\Intelligence\Models\Actor([
    'vocation_id' => 'martial_artist',
    'stats' => ['strength' => 10, 'agility' => 10, 'vitality' => 10, 'intelligence' => 10, 'wisdom' => 10]
]);
$engine = app(\App\Modules\Simulation\Services\Society\VocationEngine::class);
$newStats = $engine->calculateStats($actor);
echo "Original Stats: " . json_encode($actor->stats) . "\n";
echo "Scaled Stats:   " . json_encode($newStats) . "\n";
