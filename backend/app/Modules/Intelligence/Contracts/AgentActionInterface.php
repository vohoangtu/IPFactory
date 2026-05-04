<?php

namespace App\Modules\Intelligence\Contracts;

use App\Modules\World\Models\Universe;
use App\Modules\Intelligence\Domain\Policy\ActionResult;
use App\Modules\Intelligence\Domain\Policy\UniverseContext;
use App\Modules\Intelligence\Entities\ActorEntity;

/**
 * Strategy interface: every agent action must implement this.
 * Actions are pure — they return ActionResult, they do NOT persist anything.
 */
interface AgentActionInterface
{
    public function getType(): string;

    /**
     * Compute the action's side-effects, returned as an ActionResult.
     * Must NOT mutate any model or DB. Persistence is done by AgentAutonomyService.
     */
    public function execute(
        ActorEntity     $actor,
        Universe        $universe,
        UniverseContext $ctx,
        int             $tick,
    ): ActionResult;
}

