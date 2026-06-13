<?php

namespace App\Modules\Simulation\Actions;

use App\Modules\World\Models\World;
use App\Modules\Simulation\Models\BranchEvent;
use App\Contracts\WorldAxiomActionInterface;
use Illuminate\Support\Facades\DB;

class WorldAxiomAction
implements \App\Contracts\ActionInterface, WorldAxiomActionInterface {
    /**
     * Cập nhật Axiom của World và lan tỏa tới toàn bộ Universe con.
     */
    public function execute(World $world, array $newAxioms): array
    {
        return DB::transaction(function () use ($world, $newAxioms) {
            $oldAxioms = $world->axiom ?? [];
            $updatedAxioms = array_merge($oldAxioms, $newAxioms);
            
            $world->update(['axiom' => $updatedAxioms]);

            // Lan tỏa tới các Universe đang hoạt động
            $activeUniverses = $world->universes()->where('status', 'active')->get();

            foreach ($activeUniverses as $universe) {
                BranchEvent::create([
                    'universe_id' => $universe->id,
                    'from_tick' => (int) $universe->current_tick,
                    'event_type' => 'axiom_shift',
                    'payload' => [
                        'changed_axioms' => $newAxioms,
                        'message' => 'Một ý chí tối cao đã thay đổi quy luật của thực tại.',
                        'global_effect' => true
                    ]
                ]);

                // Ghi nhận vào Chronicle để AI Narrative nhận biết
                \App\Modules\Narrative\Models\Chronicle::create([
                    'universe_id' => $universe->id,
                    'from_tick' => $universe->current_tick,
                    'to_tick' => $universe->current_tick,
                    'type' => 'event',
                    'raw_payload' => [
                    'action' => 'legacy_event',
                    'description' => 'Các hằng số cơ bản của vũ trụ đột ngột biến đổi. Cấu trúc thực tại đang được tái định nghĩa.'
                ]
                ]);
            }

            return [
                'ok' => true,
                'affected_universes' => $activeUniverses->count(),
                'new_axioms' => $updatedAxioms
            ];
        });
    }
}

