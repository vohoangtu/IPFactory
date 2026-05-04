<?php

namespace App\Modules\Simulation\Listeners;

use App\Modules\Simulation\Events\UniverseSimulationPulsed;
use App\Modules\Narrative\Actions\CorrectionAction;
use App\Modules\Simulation\Models\UniverseSnapshot;
use Illuminate\Support\Facades\Log;

class StagnationDetectorListener
{
    public function __construct(
        protected CorrectionAction $correctionAction
    ) {}

    public function handle(UniverseSimulationPulsed $event): void
    {
        $universe = $event->universe;
        $snapshot = $event->snapshot;

        // Chỉ kiểm tra sau mỗi 10 ticks để tránh nhiễu
        if ($snapshot->tick % 10 !== 0) {
            return;
        }

        // Lấy snapshot cách đây 10 ticks
        $previousSnapshot = UniverseSnapshot::where('universe_id', $universe->id)
            ->where('tick', $snapshot->tick - 10)
            ->first();

        if (!$previousSnapshot) {
            return;
        }

        // Tính toán sự thay đổi (Inertia)
        $deltaEntropy = abs(($snapshot->entropy ?? 0) - ($previousSnapshot->entropy ?? 0));
        $deltaStability = abs(($snapshot->stability_index ?? 0) - ($previousSnapshot->stability_index ?? 0));

        // Nếu thay đổi quá nhỏ (< 0.005) sau 10 ticks -> Coi như bị kẹt (Stagnant)
        if ($deltaEntropy < 0.005 && $deltaStability < 0.005) {
            Log::info("StagnationDetector: Universe {$universe->id} detected as stagnant at tick {$snapshot->tick}. Delta Entropy: {$deltaEntropy}");
            $this->correctionAction->execute($universe, 'inertia');
        }
    }
}



