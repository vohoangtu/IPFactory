<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use Illuminate\Contracts\Console\Kernel;

$app->make(Kernel::class)->bootstrap();

echo "--- WorldOS Simulation Post-Mortem Analysis ---\n";

echo "Listing Archived Universes:\n";
$universes = Universe::whereIn('status', ['archived', 'collapsed'])->orderBy('updated_at', 'desc')->get();

foreach ($universes as $u) {
    $maxTick = UniverseSnapshot::where('universe_id', $u->id)->max('tick');
    echo "ID: {$u->id} | Status: {$u->status} | Ticks: {$maxTick} | Updated: {$u->updated_at}\n";
}

$universe = $universes->first(); // Take the most recent for now, or the one with most ticks
if (!$universe) {
    echo "No archived universe found.\n";
    exit;
}

echo "\nAnalyzing Universe ID: {$universe->id}\n";
echo "-----------------------------------------------\n";

$snapshots = UniverseSnapshot::where('universe_id', $universe->id)
    ->orderBy('tick', 'asc')
    ->get();

$events = \App\Modules\Simulation\Models\BranchEvent::where('universe_id', $universe->id)->orderBy('from_tick', 'asc')->get();

echo sprintf("%-8s | %-8s | %-8s | %-8s | %-8s | %-15s\n", "Tick", "Entropy", "Order", "Energy", "Complex", "Major Event");
echo str_repeat("-", 75) . "\n";

foreach ($snapshots as $s) {
    if ($s->tick % 50 == 0 || $s->tick == 1 || $s->tick == $snapshots->last()->tick) {
        $metrics = $s->metrics ?? [];
        $tickEvent = $events->where('from_tick', $s->tick)->first();
        $eventName = $tickEvent ? $tickEvent->event_type : "";

        echo sprintf("%-8d | %-8.4f | %-8.4f | %-8.4f | %-8.4f | %-15s\n", 
            $s->tick, 
            $s->entropy, 
            $metrics['order'] ?? 0, 
            $metrics['energy_level'] ?? 0, 
            $metrics['civilization_complexity'] ?? 0,
            substr($eventName, 0, 15)
        );
    }
}

echo "-----------------------------------------------\n";
echo "Evolution Stages Identification:\n";

$first = $snapshots->first();
$last = $snapshots->last();

if ($last->entropy > 0.9) {
    echo "- Global State: ESCHATON (Entropy saturation triggered archive)\n";
} elseif (($last->metrics['order'] ?? 0) > 0.95) {
    echo "- Global State: ASCENSION (High order triggered transition)\n";
} else {
    echo "- Global State: STABILIZED (Simulation ended normally)\n";
}

$entropyDiff = round($last->entropy - $first->entropy, 4);
echo "- Entropy Delta: " . $entropyDiff . " (" . ($entropyDiff > 0 ? "Increasing Chaos" : "Stabilizing") . ")\n";

$complexityGrowth = round(($last->metrics['civilization_complexity'] ?? 0) - ($first->metrics['civilization_complexity'] ?? 0), 4);
echo "- Complexity Growth: " . $complexityGrowth . "\n";

$forks = $events->where('event_type', 'fork')->count();
echo "- Timeline Branches: " . $forks . " forks detected.\n";
