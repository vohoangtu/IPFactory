<?php

namespace App\Modules\Simulation\Core\Runtime\Projectors;

use App\Modules\Simulation\Core\Runtime\Events\AscensionEvent;
use App\Modules\World\Models\Universe;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Simulation\Models\BranchEvent;
use Illuminate\Support\Facades\DB;

class AscensionProjector
{
    public function apply(AscensionEvent $event, Universe $universe): void
    {
        // 1. Update Universe Level
        $universe->update([
            'level' => $event->newLevel
        ]);

        // 2. Create Lore
        Chronicle::create([
            'universe_id' => $universe->id,
            'from_tick' => $event->tick,
            'to_tick' => $event->tick,
            'type' => 'ascension',
            'raw_payload' => [
                'action' => 'legacy_event',
                'description' => "Trời đất rung chuyển, rào cản thứ nguyên nứt vỡ. Thế giới tắm trong kim quang rực rỡ khi vượt qua ngưỡng giới hạn của Cấp độ {$event->oldLevel}. Toàn bộ vũ trụ đã Phi Thăng lên Tầng Thứ {$event->newLevel}!"
            ],
        ]);

        // 3. Branch Event
        BranchEvent::create([
            'universe_id' => $universe->id,
            'from_tick' => $event->tick,
            'event_type' => 'universal_ascension',
            'payload' => $event->payload(),
        ]);

        // 4. Reward: Boost supreme entities power
        $universe->supremeEntities()->update(['power_level' => DB::raw('LEAST(1.0, power_level + 0.1)')]);
    }
}

