<?php

namespace App\Modules\Simulation\Core\Runtime\Projectors;

use App\Modules\Simulation\Core\Runtime\Events\EschatonEvent;
use App\Modules\World\Models\Universe;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Simulation\Models\BranchEvent;
use App\Modules\Simulation\Models\MaterialInstance;
use App\Modules\Simulation\Core\Support\SimulationRandom;
use Illuminate\Support\Facades\DB;

class EschatonProjector
{
    public function apply(EschatonEvent $event, Universe $universe): void
    {
        // 1. Reset Universe Epoch & Level
        $universe->update([
            'epoch' => $event->newEpoch,
            'level' => 1,
            'status' => 'restarting',
        ]);

        // 2. Create Lore
        Chronicle::create([
            'universe_id' => $universe->id,
            'from_tick' => $event->tick,
            'to_tick' => $event->tick,
            'type' => 'eschaton',
            'raw_payload' => [
                'action' => 'legacy_event',
                'description' => "Tiếng chuông lụi tàn điểm. Kỷ nguyên {$event->oldEpoch} sụp đổ trong biển Entropy hỗn loạn. Chư thần ngã xuống, vạn vật tan biến vào hư vô... Một mầm sống mới đang nảy nở từ đống tro tàn của Epoch {$event->newEpoch}."
            ],
        ]);

        // 3. Branch Event
        BranchEvent::create([
            'universe_id' => $universe->id,
            'from_tick' => $event->tick,
            'event_type' => 'eschaton_reset',
            'payload' => $event->payload(),
        ]);

        // 4. Material survivability (CHUNKED for scalability)
        $survivabilityRates = config('worldos.eschaton_survivability', []);
        $defaultRate = (float) ($survivabilityRates['default'] ?? 0.1);
        $rng = new SimulationRandom((int) ($universe->seed ?? 0), (int) $event->tick, 1);

        MaterialInstance::where('universe_id', $universe->id)
            ->with('material')
            ->chunk(1000, function ($instances) use ($rng, $survivabilityRates, $defaultRate) {
                foreach ($instances as $instance) {
                    $ontology = $instance->material?->ontology ?? 'default';
                    $rate = (float) ($survivabilityRates[$ontology] ?? $defaultRate);
                    if ($rate <= 0 || $rng->float(0, 1) >= $rate) {
                        $instance->delete();
                    }
                }
            });
    }
}

