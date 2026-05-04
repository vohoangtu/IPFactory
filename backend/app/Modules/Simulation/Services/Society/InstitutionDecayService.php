<?php

namespace App\Modules\Simulation\Services\Society;

use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Simulation\Models\Civilization;
use App\Modules\Institutions\Models\InstitutionalEntity;
use App\Modules\World\Models\Universe;
use App\Modules\Narrative\Services\NarrativeScheduler;

/**
 * InstitutionDecayService — Phase 5.
 * Periodically reduce legitimacy/influence; set status declining/collapsed; Chronicle institution_collapse.
 * On collapse: ensure Civilization record exists, set collapse_tick, schedule civilization narrative job.
 */
class InstitutionDecayService
{
    public function __construct(
        protected ?NarrativeScheduler $narrativeScheduler = null
    ) {
        if ($this->narrativeScheduler === null && app()->bound(NarrativeScheduler::class)) {
            $this->narrativeScheduler = app(NarrativeScheduler::class);
        }
    }

    public function process(Universe $universe, int $tick): void
    {
        $config = config('worldos.institution', []);
        $decayRate = (float) ($config['decay_rate'] ?? 0.005);

        InstitutionalEntity::where('universe_id', $universe->id)
            ->whereNull('collapsed_at_tick')
            ->get()
            ->each(function (InstitutionalEntity $inst) use ($tick, $decayRate) {
                $legitimacy = (float) $inst->legitimacy;
                $legitimacy = max(0.0, $legitimacy - $decayRate);
                $inst->legitimacy = $legitimacy;

                $status = $inst->status ?? 'emerging';
                if ($legitimacy <= 0.2 && $status !== 'collapsed') {
                    $inst->status = 'declining';
                }
                if ($legitimacy <= 0) {
                    $inst->status = 'collapsed';
                    $inst->collapsed_at_tick = $tick;
                    Chronicle::create([
                        'universe_id' => $inst->universe_id,
                        'actor_id' => $inst->founder_actor_id,
                        'from_tick' => $tick,
                        'to_tick' => $tick,
                        'type' => 'institution_collapse',
                        'importance' => 0.3,
                        'raw_payload' => [
                            'action' => 'legacy_event',
                            'description' => "Institution collapsed: {$inst->name}.",
                        ],
                    ]);
                    $civilization = $this->ensureCivilizationForInstitution($inst, $tick);
                    if ($civilization && $this->narrativeScheduler) {
                        $this->narrativeScheduler->scheduleCivilization($universe->id, $civilization->id);
                    }
                }
                $inst->save();
            });
    }

    /**
     * Get or create a Civilization for this institution; set collapse_tick when collapsing.
     */
    protected function ensureCivilizationForInstitution(InstitutionalEntity $inst, int $collapseTick): ?Civilization
    {
        $civ = $inst->civilization_id ? Civilization::find($inst->civilization_id) : null;
        if (!$civ) {
            $civ = Civilization::create([
                'universe_id' => $inst->universe_id,
                'name' => $inst->name ?? 'Unknown Civilization',
                'origin_tick' => (int) ($inst->spawned_at_tick ?? 0),
                'collapse_tick' => $collapseTick,
                'capital_zone_id' => $inst->zone_id,
            ]);
            $inst->civilization_id = $civ->id;
            $inst->save();
        } else {
            $civ->update(['collapse_tick' => $collapseTick]);
        }
        return $civ;
    }
}


