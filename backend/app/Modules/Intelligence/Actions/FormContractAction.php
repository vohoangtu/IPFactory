<?php

namespace App\Modules\Intelligence\Actions;

use App\Modules\World\Models\Universe;
use App\Modules\Intelligence\Contracts\AgentActionInterface;
use App\Modules\Intelligence\Domain\Policy\ActionResult;
use App\Modules\Intelligence\Domain\Policy\UniverseContext;
use App\Modules\Intelligence\Entities\ActorEntity;
use App\Modules\Intelligence\Contracts\ActorRepositoryInterface;
use App\Modules\Institutions\Models\SocialContract;

class FormContractAction implements AgentActionInterface
{
    public function __construct(
        private readonly ActorRepositoryInterface $actorRepository,
    ) {}

    public function getType(): string
    {
        return 'form_contract';
    }

    public function execute(
        ActorEntity     $actor,
        Universe        $universe,
        UniverseContext $ctx,
        int             $tick,
    ): ActionResult {
        // Find allies (side-effect: creating the SocialContract is ok here as it's infra)
        $others = $this->actorRepository->findByUniverse($universe->id);
        $others = array_filter($others, fn($o) => $o->id != $actor->id && $o->isAlive);
        $others = array_slice($others, 0, 3);

        if (empty($others)) {
            return new ActionResult(null, [], null);
        }

        $names        = implode(', ', array_map(fn($o) => $o->name, $others));
        $participants = array_map(fn($o) => $o->id, $others);
        $participants[] = $actor->id;

        SocialContract::create([
            'universe_id'     => $universe->id,
            'type'            => 'mutual_defense',
            'participants'    => $participants,
            'strictness'      => rand(30, 80) / 100,
            'duration'        => 100,
            'created_at_tick' => $tick,
            'expires_at_tick' => $tick + 100,
        ]);

        return new ActionResult(
            biographyEntry: "T{$tick}: Ký kết giao ước liên thủ với {$names}.",
            universeImpact: [
                'entropy'         => -0.02,
                'stability_index' => +0.03,
            ],
            chronicleEntry: [
                'type' => 'social_contract',
                'raw_payload' => [
                    'action'      => 'form_contract',
                    'description' => "GIAO ƯỚC MỚI: {$actor->name} và các đồng minh thiết lập khế ước tương trợ, đặt nền móng cho trật tự mới.",
                ],
            ],
        );
    }
}

