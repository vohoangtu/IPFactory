<?php

namespace App\Modules\Simulation\Core\Effects;

use App\Modules\Simulation\Core\Contracts\Effect;
use App\Modules\Simulation\Core\Runtime\State\WorldStateMutable;

/**
 * Applies zone conquest outcome to state: winner zone gains order / lower entropy,
 * loser zone loses order / higher entropy. Only mutates state; Chronicle/BranchEvent
 * are handled elsewhere (listener or ChronicleWriter).
 */
final class ZoneConquestEffect implements Effect
{
    public function __construct(
        private readonly string $winnerZoneId,
        private readonly string $loserZoneId,
        private readonly float $winnerEntropyDelta = -0.1,
        private readonly float $loserEntropyDelta = 0.2,
        private readonly float $loserOrderDelta = -0.3,
    ) {
    }

    public function getWinnerZoneId(): string
    {
        return $this->winnerZoneId;
    }

    public function getLoserZoneId(): string
    {
        return $this->loserZoneId;
    }

    public function apply(WorldStateMutable $state): void
    {
        $zones = $state->getZones();
        $changed = false;
        foreach ($zones as $i => $zone) {
            $id = (string) ($zone['id'] ?? '');
            if ($id === $this->winnerZoneId) {
                if (!isset($zones[$i]['state'])) {
                    $zones[$i]['state'] = [];
                }
                $zones[$i]['state']['entropy'] = max(0, ($zones[$i]['state']['entropy'] ?? 0) + $this->winnerEntropyDelta);
                $zones[$i]['conflict_status'] = 'active';
                $changed = true;
            }
            if ($id === $this->loserZoneId) {
                if (!isset($zones[$i]['state'])) {
                    $zones[$i]['state'] = [];
                }
                $zones[$i]['state']['entropy'] = min(1.0, ($zones[$i]['state']['entropy'] ?? 0) + $this->loserEntropyDelta);
                $zones[$i]['state']['order'] = max(0, ($zones[$i]['state']['order'] ?? 0) + $this->loserOrderDelta);
                $zones[$i]['conflict_status'] = 'active';
                $changed = true;
            }
        }
        if ($changed) {
            $state->setZones($zones);
        }
    }
}
