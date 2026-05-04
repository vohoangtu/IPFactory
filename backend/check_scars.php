<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$scars = \App\Modules\Narrative\Models\Chronicle::where('universe_id', 1)->orderBy('id', 'desc')->limit(10)->get();
echo "--- LATEST SCARS ---\n";
foreach ($scars as $s) {
    echo "ID: {$s->id} | Type: {$s->type} | Imp: " . round($s->importance, 4) . " | Tick: {$s->from_tick}\n";
}

$oldest = \App\Modules\Narrative\Models\Chronicle::where('universe_id', 1)->orderBy('id', 'asc')->limit(5)->get();
echo "\n--- OLDEST SCARS (DECAY CHECK) ---\n";
foreach ($oldest as $s) {
    echo "ID: {$s->id} | Type: {$s->type} | Imp: " . round($s->importance, 4) . " | Tick: {$s->from_tick}\n";
}
