<?php

namespace App\Modules\Narrative\Actions;

use App\Modules\World\Models\Universe;
use App\Modules\Intelligence\Models\Actor;
use App\Modules\Intelligence\Models\LegendaryAgent;
use App\Modules\Institutions\Models\InstitutionalEntity;
use App\Modules\Institutions\Models\SupremeEntity;
use App\Modules\Narrative\Contracts\ChronicleRepositoryInterface;
use App\Modules\Narrative\Entities\ChronicleEntity;
use App\Modules\Simulation\Core\Engines\Meta\WorldWillEngine;

/**
 * Ascension Action: Triggers the ascension of high-tier entities to Supreme status.
 */
class AscensionAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        protected WorldWillEngine $willEngine,
        protected ChronicleRepositoryInterface $chronicleRepository
    ) {}

    /**
     * Scan candidate institutions and actors for ascension.
     */
    public function execute(Universe $universe, int $tick): void
    {
        // 1. Institutional Ascension
        // Threshold: Legitimacy > 0.98, Capacity > 500, Min Tick > 200
        if ($tick < 200) return;

        $candidates = InstitutionalEntity::where('universe_id', $universe->id)
            ->whereNull('collapsed_at_tick')
            ->where('legitimacy', '>', 0.98)
            ->where('org_capacity', '>', 500)
            ->get();

        foreach ($candidates as $inst) {
            $this->ascendInstitution($inst, $tick);
        }

        // 2. Heroic Actor Ascension (Demi-gods).
        $this->ascendHeroicActors($universe, $tick);
    }

    protected function ascendInstitution(InstitutionalEntity $inst, int $tick): void
    {
        // Prevent duplicate ascension
        $exists = SupremeEntity::where('universe_id', $inst->universe_id)
            ->where('name', $inst->name)
            ->exists();
        if ($exists) return;

        $alignment = $this->willEngine->calculateAlignment($inst->universe);
        $dominant = $this->willEngine->getDominantAlignment($alignment);

        $supreme = SupremeEntity::create([
            'universe_id' => $inst->universe_id,
            'name' => "Archon {$inst->name}",
            'entity_type' => 'ascended_institution',
            'domain' => $this->mapDomain($inst->entity_type, $dominant),
            'description' => "Thực thể tối cao thăng hoa từ định chế {$inst->name}. Người bảo hộ của {$dominant}.",
            'power_level' => 1.0,
            'alignment' => $alignment,
            'status' => 'active',
            'ascended_at_tick' => $tick,
        ]);

        $chronicleEntity = ChronicleEntity::create([
            'universe_id' => $inst->universe_id,
            'from_tick' => $tick,
            'to_tick' => $tick,
            'type' => 'ascension_event',
            'content' => "SỰ THĂNG HOA TỐI CAO: Định chế {$inst->name} đã vượt ngưỡng phàm trần, trở thành {$supreme->name} cai quản cõi {$supreme->domain}.",
            'importance' => 1.0,
            'raw_payload' => [
                'action' => 'legacy_event',
                'description' => "SỰ THĂNG HOA TỐI CAO: Định chế {$inst->name} đã vượt ngưỡng phàm trần, trở thành {$supreme->name} cai quản cõi {$supreme->domain}."
            ],
        ]);
        $this->chronicleRepository->save($chronicleEntity);

        // Consume origin institution? Or mark as "Divine Presence"
        // Let's keep the institution but boost its capacity as a "temple/base"
        $inst->update([
            'org_capacity' => $inst->org_capacity + 500,
            'legitimacy' => 1.0
        ]);
    }

    protected function mapDomain(string $instType, string $alignment): string
    {
        return match($alignment) {
            'spirituality' => 'Cõi Vĩnh Hằng (Eternal)',
            'hardtech' => 'Cơ Giới Đỉnh Cao (Singularity)',
            'entropy' => 'Vực Thẳm Hư Vô (The Void)',
            default => 'Hư thực chi giới',
        };
    }

    /**
     * Scan for heroic actors with sufficient influence to ascend to LegendaryAgent status.
     *
     * Thresholds:
     * - Actor must be marked is_heroic.
     * - Heroic type must be non-null.
     * - Not already ascended (no existing LegendaryAgent with matching original_agent_id).
     * - Tick maturity: the actor must have been alive for at least 100 ticks.
     */
    private function ascendHeroicActors(Universe $universe, int $tick): void
    {
        // Already-ascended agent IDs to exclude.
        $alreadyLegendary = LegendaryAgent::where('universe_id', $universe->id)
            ->whereNotNull('original_agent_id')
            ->pluck('original_agent_id')
            ->toArray();

        $candidates = Actor::where('universe_id', $universe->id)
            ->where('is_alive', true)
            ->where('is_heroic', true)
            ->whereNotNull('heroic_type')
            ->where('heroic_type', '!=', '')
            ->whereNotIn('id', $alreadyLegendary)
            ->where('birth_tick', '>', 0)
            ->whereRaw('(? - birth_tick) > 100', [$tick])
            ->get();

        foreach ($candidates as $actor) {
            $this->ascendHeroicActor($actor, $universe, $tick);
        }
    }

    /**
     * Ascend a single heroic actor to LegendaryAgent (demi-god) status.
     */
    private function ascendHeroicActor(Actor $actor, Universe $universe, int $tick): void
    {
        $alignment = $this->willEngine->calculateAlignment($universe);
        $dominant = $this->willEngine->getDominantAlignment($alignment);

        $metrics = is_array($actor->metrics) ? $actor->metrics : (json_decode($actor->metrics ?? '{}', true) ?? []);
        $influence = (float) ($metrics['influence'] ?? $metrics['reputation'] ?? $metrics['fame'] ?? 0.5);

        $legendary = LegendaryAgent::create([
            'universe_id' => $universe->id,
            'original_agent_id' => $actor->id,
            'name' => $actor->name,
            'archetype' => $actor->archetype ?? 'hero',
            'fate_tags' => [$actor->heroic_type, $dominant, 'ascended_at_tick_' . $tick],
            'biography' => $actor->biography ?? "A heroic soul that transcended mortality at tick {$tick}.",
            'image_url' => null,
            'tick_discovered' => $tick,
            'is_transcendental' => $influence > 0.8,
            'soul_metadata' => [
                'parent_actor_id' => $actor->id,
                'original_archetype' => $actor->archetype,
                'birth_tick' => $actor->birth_tick,
                'generation' => $actor->generation,
                'ascension_alignment' => $dominant,
                'ascension_influence' => $influence,
            ],
            'heresy_score' => $influence > 0.9 ? 0.1 : 0.0,
            'is_isekai' => false,
        ]);

        // Mark the source actor as having reached heroic completion.
        $actor->forceFill([
            'hero_stage' => 'ascended',
            'is_alive' => false,
            'death_tick' => $tick,
        ])->save();

        // Chronicle this ascension event.
        $chronicle = ChronicleEntity::create([
            'universe_id' => $universe->id,
            'from_tick' => $tick,
            'to_tick' => $tick,
            'type' => 'ascension_event',
            'content' => "ANH HÙNG THĂNG HOA: {$actor->name}, với lý tưởng {$actor->heroic_type}, "
                       . "đã vượt qua giới hạn phàm nhân, trở thành một Huyền Thoại. "
                       . "Linh hồn họ giờ đây cư ngụ trong cõi {$dominant}.",
            'importance' => 1.0,
            'raw_payload' => [
                'action' => 'heroic_ascension',
                'actor_id' => $actor->id,
                'legendary_id' => $legendary->id,
                'heroic_type' => $actor->heroic_type,
                'alignment' => $dominant,
                'description' => "ANH HÙNG THĂNG HOA: {$actor->name} → LegendaryAgent #{$legendary->id}",
            ],
        ]);
        $this->chronicleRepository->save($chronicle);
    }
}


