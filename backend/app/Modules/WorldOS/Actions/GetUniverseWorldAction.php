<?php

declare(strict_types=1);

namespace App\Modules\WorldOS\Actions;

use App\Contracts\ActionInterface;
use App\Modules\World\Models\DiplomaticTreaty;
use App\Modules\World\Models\Epoch;
use App\Modules\World\Models\Religion;
use App\Modules\World\Models\Universe;
use Illuminate\Support\Facades\DB;

class GetUniverseWorldAction implements ActionInterface
{
    private const RELIGION_LIMIT = 20;
    private const TREATY_LIMIT = 50;
    private const TECHNOLOGY_LIMIT = 50;

    /** @return array{data: array<string, mixed>} */
    public function handle(int $universeId): array
    {
        $universe = Universe::query()->findOrFail($universeId);

        return [
            'data' => [
                'universe_id' => $universe->id,
                'world_id' => $universe->world_id !== null ? (int) $universe->world_id : null,
                'epoch' => $this->currentEpoch($universe),
                'religions' => $this->religions($universeId),
                'treaties' => $this->treaties($universeId),
                'technologies' => $this->technologies($universeId),
            ],
        ];
    }

    /** @return array<string, mixed>|null */
    private function currentEpoch(Universe $universe): ?array
    {
        if ($universe->world_id === null) {
            return null;
        }

        $epoch = Epoch::query()
            ->where('world_id', $universe->world_id)
            ->where('status', 'active')
            ->orderByDesc('start_tick')
            ->first()
            ?? Epoch::query()
                ->where('world_id', $universe->world_id)
                ->where('start_tick', '<=', (int) $universe->current_tick)
                ->orderByDesc('start_tick')
                ->first();

        if ($epoch === null) {
            return null;
        }

        return [
            'id' => $epoch->id,
            'name' => $epoch->name,
            'theme' => $epoch->theme,
            'description' => $epoch->description,
            'start_tick' => $epoch->start_tick !== null ? (int) $epoch->start_tick : null,
            'end_tick' => $epoch->end_tick !== null ? (int) $epoch->end_tick : null,
            'status' => $epoch->status,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function religions(int $universeId): array
    {
        return Religion::query()
            ->where('universe_id', $universeId)
            ->orderByDesc('followers')
            ->limit(self::RELIGION_LIMIT)
            ->get()
            ->map(fn (Religion $r): array => [
                'id' => $r->id,
                'name' => $r->name,
                'followers' => (int) $r->followers,
                'spread_rate' => $r->spread_rate !== null ? (float) $r->spread_rate : null,
                'doctrine' => $r->doctrine,
            ])
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function treaties(int $universeId): array
    {
        return DiplomaticTreaty::query()
            ->where('universe_id', $universeId)
            ->where('is_active', true)
            ->orderByDesc('started_at_tick')
            ->limit(self::TREATY_LIMIT)
            ->get()
            ->map(fn (DiplomaticTreaty $t): array => [
                'id' => $t->id,
                'treaty_type' => $t->treaty_type,
                'source_civ_id' => $t->source_civ_id,
                'target_civ_id' => $t->target_civ_id,
                'started_at_tick' => (int) $t->started_at_tick,
                'ends_at_tick' => $t->ends_at_tick !== null ? (int) $t->ends_at_tick : null,
            ])
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function technologies(int $universeId): array
    {
        return DB::table('technologies')
            ->join('actor_technologies', 'actor_technologies.technology_id', '=', 'technologies.id')
            ->join('actors', 'actors.id', '=', 'actor_technologies.actor_id')
            ->where('actors.universe_id', $universeId)
            ->groupBy('technologies.id', 'technologies.name', 'technologies.code')
            ->orderByDesc(DB::raw('COUNT(actor_technologies.id)'))
            ->limit(self::TECHNOLOGY_LIMIT)
            ->select([
                'technologies.id',
                'technologies.name',
                'technologies.code',
                DB::raw('COUNT(actor_technologies.id) as adopters'),
                DB::raw('AVG(actor_technologies.level) as avg_level'),
            ])
            ->get()
            ->map(fn (object $row): array => [
                'id' => (int) $row->id,
                'name' => $row->name,
                'code' => $row->code,
                'adopters' => (int) $row->adopters,
                'avg_level' => round((float) $row->avg_level, 3),
            ])
            ->all();
    }
}
