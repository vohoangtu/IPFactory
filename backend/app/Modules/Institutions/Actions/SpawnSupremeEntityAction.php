<?php

namespace App\Modules\Institutions\Actions;

use App\Modules\Intelligence\Models\Actor;
use App\Modules\Intelligence\Models\ActorEvent;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Simulation\Models\BranchEvent;
use App\Modules\World\Models\Universe;
use App\Modules\Institutions\Models\SupremeEntity as SupremeEntityModel;
use App\Modules\Institutions\Contracts\SupremeEntityRepositoryInterface;
use App\Modules\Institutions\Entities\SupremeEntity;

class SpawnSupremeEntityAction
implements \App\Contracts\ActionInterface {
    /** Default 17-D trait vector for Great Person (neutral/balanced). */
    private const DEFAULT_TRAITS = [0.5, 0.5, 0.5, 0.5, 0.5, 0.5, 0.5, 0.5, 0.5, 0.5, 0.5, 0.5, 0.5, 0.5, 0.5, 0.5, 0.5];

    public function __construct(
        private SupremeEntityRepositoryInterface $supremeEntityRepository
    ) {}

    public function handle(Universe $universe, int $tick, array $data, ?int $sourceActorId = null): SupremeEntity
    {
        $entity = new SupremeEntity(
            id: null,
            universeId: $universe->id,
            name: $data['name'],
            entityType: $data['entity_type'],
            domain: $data['domain'],
            description: $data['description'] ?? null,
            powerLevel: $data['power_level'] ?? 1.0,
            alignment: $data['alignment'] ?? [],
            karma: 0.5,
            status: 'active',
            ascendedAtTick: $tick
        );

        $supremeId = $this->supremeEntityRepository->save($entity);

        $flavorText = $sourceActorId
            ? "PHI THĂNG ANH HÙNG: Một thực thể mới đã bứt phá giới hạn phàm trần, trở thành [{$entity->name}]. Ngôi sao mới rực sáng trên bầu trời thần thoại!"
            : "Biến cố cấp vũ trụ: Sự kiện Giáng Lâm Thực Thể! [{$entity->name}] - Danh hiệu: {$entity->domain} đã đản sinh.";

        $chronicle = Chronicle::create([
            'universe_id' => $universe->id,
            'from_tick' => $tick,
            'to_tick' => $tick,
            'type' => 'supreme_emergence',
            'importance' => 0.8,
            'raw_payload' => [
                'action' => 'legacy_event',
                'description' => $flavorText
            ],
        ]);

        BranchEvent::create([
            'universe_id' => $universe->id,
            'from_tick' => $tick,
            'event_type' => 'supreme_emergence',
            'payload' => array_merge($data, [
                'entity_id' => $supremeId,
                'source_actor_id' => $sourceActorId,
                'description' => $flavorText,
            ]),
        ]);

        $actorId = null;
        $entityType = $data['entity_type'] ?? '';
        if (str_starts_with($entityType, 'great_person_')) {
            $actor = Actor::create([
                'universe_id' => $universe->id,
                'name' => $entity->name,
                'archetype' => $entityType,
                'traits' => self::DEFAULT_TRAITS,
                'biography' => $entity->description ?? 'Vĩ nhân xuất hiện trong biên niên sử.',
                'is_alive' => true,
                'generation' => 1,
                'birth_tick' => $tick,
                'life_stage' => 'adult',
                'trait_scan_status' => 'estimated',
                'metrics' => [
                    'power_level' => $entity->powerLevel,
                    'domain' => $entity->domain,
                ],
            ]);
            $actorId = $actor->id;
            SupremeEntityModel::where('id', $supremeId)->update(['actor_id' => $actorId]);
            $chronicle->update(['actor_id' => $actorId]);
            ActorEvent::create([
                'actor_id' => $actor->id,
                'tick' => $tick,
                'event_type' => 'great_person_emergence',
                'context' => ['supreme_entity_id' => $supremeId, 'domain' => $entity->domain],
            ]);
        }

        return new SupremeEntity(
            id: $supremeId,
            universeId: $entity->universeId,
            name: $entity->name,
            entityType: $entity->entityType,
            domain: $entity->domain,
            description: $entity->description,
            powerLevel: $entity->powerLevel,
            alignment: $entity->alignment,
            karma: $entity->karma,
            karmaMetadata: $entity->karmaMetadata,
            status: $entity->status,
            ascendedAtTick: $entity->ascendedAtTick,
            fallenAtTick: $entity->fallenAtTick,
            actorId: $actorId
        );
    }
}

