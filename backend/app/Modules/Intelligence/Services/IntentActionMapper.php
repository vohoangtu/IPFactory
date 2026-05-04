<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\World\Models\Universe;
use App\Modules\Intelligence\Actions\FormContractAction;
use App\Modules\Intelligence\Actions\MigrateAction;
use App\Modules\Intelligence\Actions\PropagateMythAction;
use App\Modules\Intelligence\Actions\RevoltAction;
use App\Modules\Intelligence\Contracts\ActorRepositoryInterface;
use App\Modules\Intelligence\Domain\Policy\ActionResult;
use App\Modules\Intelligence\Domain\Policy\IntentResponse;
use App\Modules\Intelligence\Domain\Policy\UniverseContext;
use App\Modules\Intelligence\Entities\ActorEntity;

/**
 * Maps a narrative-loom IntentResponse into an ActionResult.
 * Key difference from Strategy Actions: uses intent.reasoning as the biography entry
 * and scales universe impact by intent.intensity.
 */
class IntentActionMapper
{
    public function __construct(
        private readonly ActorRepositoryInterface $actorRepository,
    ) {}

    public function execute(
        IntentResponse  $intent,
        ActorEntity     $actor,
        Universe        $universe,
        UniverseContext $ctx,
        int             $tick,
    ): ?ActionResult {
        return match ($intent->action) {
            'revolt'          => $this->revolt($intent, $actor, $tick),
            'form_contract'   => $this->formContract($intent, $actor, $universe, $tick),
            'migrate'         => $this->migrate($intent, $actor, $tick),
            'propagate_myth'  => $this->propagateMith($intent, $actor, $tick),
            'suppress_revolt' => $this->suppressRevolt($intent, $actor, $tick),
            'trade'           => $this->trade($intent, $actor, $tick),
            default           => null,
        };
    }

    // ── Action implementations (intent-driven, not fixed deltas) ─────────────

    private function revolt(IntentResponse $intent, ActorEntity $actor, int $tick): ActionResult
    {
        $scale = $intent->intensity;
        return new ActionResult(
            biographyEntry: "T{$tick}: {$intent->reasoning}",
            universeImpact: [
                'entropy'         => round(+0.05 * $scale, 4),
                'stability_index' => round(-0.05 * $scale, 4),
            ],
            chronicleEntry: [
                'type' => 'revolt',
                'raw_payload' => [
                    'action'      => 'revolt',
                    'description' => "BIẾN LOẠN [{$actor->name}]: {$intent->reasoning}",
                ],
            ],
        );
    }

    private function formContract(IntentResponse $intent, ActorEntity $actor, Universe $universe, int $tick): ActionResult
    {
        $others = $this->actorRepository->findByUniverse($universe->id);
        $others = array_filter($others, fn($o) => $o->id != $actor->id && $o->isAlive);

        // If LLM specified a target name, prefer that actor
        if ($intent->target) {
            $named = array_filter($others, fn($o) => str_contains($o->name, $intent->target));
            if (!empty($named)) {
                $others = $named;
            }
        }

        $others = array_slice($others, 0, 3);
        if (empty($others)) {
            return new ActionResult("T{$tick}: {$intent->reasoning} (nhưng không tìm được đồng minh)", [], null);
        }

        $names = implode(', ', array_map(fn($o) => $o->name, $others));
        $ids   = array_map(fn($o) => $o->id, $others);
        $ids[] = $actor->id;

        \App\Modules\Institutions\Models\SocialContract::create([
            'universe_id'     => $universe->id,
            'type'            => 'mutual_defense',
            'participants'    => $ids,
            'strictness'      => min(0.9, 0.4 + $intent->intensity * 0.5),
            'duration'        => 100,
            'created_at_tick' => $tick,
            'expires_at_tick' => $tick + 100,
        ]);

        return new ActionResult(
            biographyEntry: "T{$tick}: {$intent->reasoning} (cùng {$names})",
            universeImpact: [
                'entropy'         => round(-0.02 * $intent->intensity, 4),
                'stability_index' => round(+0.03 * $intent->intensity, 4),
            ],
            chronicleEntry: [
                'type' => 'social_contract',
                'raw_payload' => [
                    'action'      => 'form_contract',
                    'description' => "GIAO ƯỚC [{$actor->name} & {$names}]: {$intent->reasoning}",
                ],
            ],
        );
    }

    private function migrate(IntentResponse $intent, ActorEntity $actor, int $tick): ActionResult
    {
        return new ActionResult(
            biographyEntry: "T{$tick}: {$intent->reasoning}",
            universeImpact: ['entropy' => round(+0.01 * $intent->intensity, 4)],
            chronicleEntry: null,
        );
    }

    private function propagateMith(IntentResponse $intent, ActorEntity $actor, int $tick): ActionResult
    {
        return new ActionResult(
            biographyEntry: "T{$tick}: {$intent->reasoning}",
            universeImpact: ['metrics.myth_intensity' => round(+0.05 * $intent->intensity, 4)],
            chronicleEntry: [
                'type' => 'myth',
                'raw_payload' => [
                    'action'      => 'propagate_myth',
                    'description' => "{$actor->name}: {$intent->reasoning}",
                ],
            ],
        );
    }

    private function suppressRevolt(IntentResponse $intent, ActorEntity $actor, int $tick): ActionResult
    {
        return new ActionResult(
            biographyEntry: "T{$tick}: {$intent->reasoning}",
            universeImpact: [
                'entropy'         => round(-0.03 * $intent->intensity, 4),
                'stability_index' => round(+0.04 * $intent->intensity, 4),
            ],
            chronicleEntry: null,
        );
    }

    private function trade(IntentResponse $intent, ActorEntity $actor, int $tick): ActionResult
    {
        return new ActionResult(
            biographyEntry: "T{$tick}: {$intent->reasoning}",
            universeImpact: ['stability_index' => round(+0.02 * $intent->intensity, 4)],
            chronicleEntry: null,
        );
    }
}

