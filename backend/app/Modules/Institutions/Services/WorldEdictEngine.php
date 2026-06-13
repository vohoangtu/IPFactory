<?php

namespace App\Modules\Institutions\Services;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Institutions\Models\SupremeEntity;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Simulation\Models\BranchEvent;
use App\Contracts\WorldEdictEngineInterface;

class WorldEdictEngine implements WorldEdictEngineInterface
{
    /**
     * Define the dictionary of possible Edicts and their multipliers.
     * key: Edict ID, value: ['target' => string, 'multiplier' => float, 'name' => string, 'flavor' => string]
     */
    protected array $edictDictionary = [
        'heavenly_tribulation' => [
            'target' => 'entropy',
            'multiplier' => 2.0,
            'name' => 'Thiên Kiếp Lôi Đình',
            'flavor' => 'Tà thần rống giận, Thiên Kiếp giáng xuống. Vạn vật phân rã nhanh gấp đôi thường ngày.'
        ],
        'reiki_revival' => [
            'target' => 'order',
            'multiplier' => 3.0,
            'name' => 'Linh Khí Phục Tô',
            'flavor' => 'Thiên Đạo ban ân, cấm chế bị phá vỡ. Linh khí dồi dào khiến thế giới thăng hoa mạnh mẽ.'
        ],
        'age_of_chaos' => [
            'target' => 'trauma',
            'multiplier' => 2.5,
            'name' => 'Kỷ Nguyên Hỗn Độn',
            'flavor' => 'Thần ma loạn vũ, Dị Thú trỗi dậy. Mọi mất mát và tổn thương trên thế giới đều bị khuếch đại sâu sắc.'
        ],
        'divine_inspiration' => [
            'target' => 'innovation',
            'multiplier' => 2.0,
            'name' => 'Thần Khải',
            'flavor' => 'Trí huệ từ cõi trên rót vào nhân loại, kỷ nguyên bùng nổ ý tưởng bắt đầu.'
        ]
    ];

    /**
     * Process active edicts and inject them into the metrics for PressureResolver.
     */
    public function decree(Universe $universe, UniverseSnapshot $snapshot): void
    {
        if (!$universe->world) {
            return;
        }

        $metrics = is_string($snapshot->metrics) ? json_decode($snapshot->metrics, true) : ($snapshot->metrics ?? []);
        
        // 1. Sync Meta-Edicts from World Axiom
        $this->syncMetaEdicts($universe, $metrics);

        // 2. Evaluate current Supreme Entities to see if they decree a new Law
        $this->evaluateNewDecrees($universe, $snapshot->tick, $metrics);

        // 3. Check Expiry (Meta-Edicts are exempt)
        $this->processExpiry($snapshot->tick, $metrics);

        // Save back: only persist when snapshot exists (avoid saving virtual snapshot)
        if ($snapshot->exists) {
            $snapshot->metrics = $metrics;
            $snapshot->save();
        } else {
            $latest = \App\Modules\Simulation\Models\UniverseSnapshot::where('universe_id', $universe->id)
                ->orderByDesc('tick')
                ->first();
            if ($latest) {
                $latest->metrics = array_merge($latest->metrics ?? [], $metrics);
                $latest->save();
            }
        }
    }

    protected function syncMetaEdicts(Universe $universe, array &$metrics): void
    {
        $world = $universe->world;
        $axiom = $world->axiom ?? [];
        $metaEdicts = $axiom['meta_edicts'] ?? [];

        if (empty($metaEdicts)) return;

        $activeEdicts = $metrics['active_edicts'] ?? [];

        foreach ($metaEdicts as $id => $data) {
            if (!isset($activeEdicts[$id])) {
                $edictDef = $this->edictDictionary[$id] ?? null;
                if (!$edictDef) continue;

                $activeEdicts[$id] = [
                    'id' => $id,
                    'decreed_by' => $data['decreed_by'] ?? 'The Origin',
                    'name' => $edictDef['name'],
                    'target' => $edictDef['target'],
                    'multiplier' => $edictDef['multiplier'],
                    'expires_at' => null, // Immortal
                    'is_meta' => true
                ];
            }
        }

        $metrics['active_edicts'] = $activeEdicts;
    }

    public function activateEdict(Universe $universe, int $tick, array &$metrics, string $edictId, string $decreedBy, string $narrativeContext = ''): bool
    {
        if (!isset($this->edictDictionary[$edictId])) return false;

        $activeEdicts = $metrics['active_edicts'] ?? [];
        if (isset($activeEdicts[$edictId])) return false;

        $edictDef = $this->edictDictionary[$edictId];
        
        $activeEdicts[$edictId] = [
            'id' => $edictId,
            'decreed_by' => $decreedBy,
            'name' => $edictDef['name'],
            'target' => $edictDef['target'],
            'multiplier' => $edictDef['multiplier'],
            'expires_at' => $tick + 10 
        ];

        $metrics['active_edicts'] = $activeEdicts;

        $flavor = "THIÊN ĐẠO BIẾN CHUYỂN: {$narrativeContext}. {$decreedBy} đã ban bố [{$edictDef['name']}]. {$edictDef['flavor']}";

        Chronicle::create([
            'universe_id' => $universe->id,
            'from_tick' => $tick,
            'to_tick' => $tick,
            'type' => 'edict_decree',
            'raw_payload' => [
                'action' => 'legacy_event',
                'description' => $flavor
            ],
        ]);

        BranchEvent::create([
            'universe_id' => $universe->id,
            'from_tick' => $tick,
            'event_type' => 'edict_decree',
            'payload' => [
                'entity' => $decreedBy,
                'edict_name' => $edictDef['name'],
                'description' => $flavor,
            ],
        ]);

        return true;
    }

    public function getEdictDictionary(): array
    {
        return $this->edictDictionary;
    }

    private function evaluateNewDecrees(Universe $universe, int $tick, array &$metrics): void
    {
        $entities = SupremeEntity::where('universe_id', $universe->id)
            ->where('status', 'active')
            ->get();

        foreach ($entities as $entity) {
            // High power entities can impose their will on reality
            if ($entity->power_level > 0.8 && mt_rand(1, 100) <= 2) { 
                $chosenEdictId = $this->chooseEdictForEntity($entity);
                if ($chosenEdictId) {
                    $context = "Dưới uy áp khủng khiếp của {$entity->name}, trật tự thực tại đã bị bẻ cong";
                    $this->activateEdict($universe, $tick, $metrics, $chosenEdictId, $entity->name, $context);
                }
            }
        }
    }

    private function processExpiry(int $tick, array &$metrics): void
    {
        $edicts = $metrics['active_edicts'] ?? [];
        foreach ($edicts as $id => $data) {
            if (isset($data['expires_at']) && $data['expires_at'] !== null && $tick >= $data['expires_at']) {
                unset($edicts[$id]); // Expire the law
            }
        }
        $metrics['active_edicts'] = $edicts;
    }

    private function chooseEdictForEntity(SupremeEntity $entity): ?string
    {
        if ($entity->entity_type === 'world_will') {
            return mt_rand(0, 1) ? 'reiki_revival' : 'divine_inspiration';
        }
        if ($entity->entity_type === 'outer_god') {
            return mt_rand(0, 1) ? 'heavenly_tribulation' : 'age_of_chaos';
        }
        if ($entity->entity_type === 'primordial_beast') {
            return 'age_of_chaos';
        }
        // Deities
        return array_rand($this->edictDictionary); // random domain
    }
}

