<?php

namespace App\Modules\Narrative\Actions;

use App\Modules\World\Models\Universe;
use App\Modules\Intelligence\Models\LegendaryAgent;
use App\Modules\Narrative\Services\TraitMapper;
use App\Modules\Narrative\Services\HeroImageService;
use App\Modules\Narrative\Actions\ApplyVisualMutationAction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * ArchetypeShiftAction: Manages agent evolution based on their life experiences (traits) (§V11).
 * High-brawny agents become Warlords, high-believers become Zealots.
 */
class ArchetypeShiftAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        protected \App\Modules\Intelligence\Actions\UpdateArchetypeAction $updateArchetypeAction,
        protected HeroImageService $heroImage,
        protected ApplyVisualMutationAction $applyMutation,
        protected \App\Modules\Intelligence\Domain\Phase\PhaseDetector $phaseDetector
    ) {}

    /**
     * Scan zones and evolve agents if necessary.
     */
    public function execute(Universe $universe): void
    {
        $latest = $universe->snapshots()->orderByDesc('tick')->first();
        if (!$latest) return;

        $zones = ($latest->state_vector ?? [])['zones'] ?? [];
        $hasChanges = false;
        
        $entropy = (float)($universe->entropy ?? 0.5);
        $worldAxiom = $universe->world->axioms ?? [];
        
        // Get PhaseScore for better classification
        $polarization = $latest->metrics['polarization_index'] ?? 0.0;
        $phaseScore = $this->phaseDetector->detect($entropy, $polarization, $universe->level ?? 1);

        foreach ($zones as &$z) {
            $agents = $z['state']['agents'] ?? [];
            foreach ($agents as &$agent) {
                // Convert raw agent array to ActorState for the new classifier
                $actorState = new \App\Modules\Intelligence\Entities\ActorState(
                    id: $agent['id'] ?? 0,
                    universeId: $universe->id,
                    name: $agent['name'] ?? 'Ẩn danh',
                    archetype: $agent['archetype'] ?? 'Commoner',
                    traits: $agent['trait_vector'] ?? [],
                    metrics: $agent['metrics'] ?? [],
                    isAlive: true
                );

                $zoneFields = $z['state']['fields'] ?? [];

                $updatedState = $this->updateArchetypeAction->handle(
                    $actorState, 
                    $worldAxiom, 
                    $entropy, 
                    [], // Ratios could be passed here if calculated
                    $phaseScore,
                    $zoneFields
                );

                if ($updatedState->archetype !== $actorState->archetype) {
                    $agent['archetype'] = $updatedState->archetype;
                    $agent['memory'][] = "Định mệnh thay đổi: Chuyển sang {$updatedState->archetype}";
                    Log::info("MYTHOS: Agent #{$agent['id']} shifted to {$updatedState->archetype}");
                    $hasChanges = true;
                }

                // Sync back metrics (like stable cycles)
                $agent['metrics'] = $updatedState->metrics;

                // Check for Fate Tags (Legacy or new logic)
                // For now, keep the fate tags logic if it depends on traits directly
                // ...
            }
            $z['state']['agents'] = $agents;
        }

        if ($hasChanges) {
            $latest->update(['state_vector' => ['zones' => $zones] + ($latest->state_vector ?? [])]);
        }
    }

    protected function persistLegend(Universe $universe, array &$agent, int $tick): void
    {
        $fateTags = $agent['fate_tags'] ?? [];
        $isTranscendental = in_array("Awareness_of_the_Clock (Nhận thức Dòng chảy)", $fateTags) || 
                            in_array("Simulation_Skepticism (Kẻ Nghi Ngờ Thực Tại)", $fateTags);

        $legend = LegendaryAgent::updateOrCreate(
            [
                'universe_id' => $universe->id,
                'original_agent_id' => $agent['id'],
            ],
            [
                'name' => $agent['name'] ?? 'Ẩn danh',
                'archetype' => $agent['archetype'] ?? 'Commoner',
                'fate_tags' => $fateTags,
                'tick_discovered' => $tick,
                'is_transcendental' => DB::raw("is_transcendental OR {$isTranscendental}") // Once transcendental, always transcendental
            ]
        );

        // Update exact flag if it was false but expression made it true (just to be clean)
        if ($isTranscendental && !$legend->is_transcendental) {
             $legend->is_transcendental = true;
             $legend->save();
             Log::alert("TRANSCENDENCE: Legend [{$legend->name}] has shattered the Fourth Wall in Universe #{$universe->id}.");
        }

        if (!$legend->image_url) {
            $legend->image_url = $this->heroImage->generatePortrait($legend);
            $legend->save();
        }

        // Phase 73: Trigger Genetic Pressure (§V13)
        // If the agent has high intensity/entropy traits, apply mutation pressure
        $this->applyMutationPressure($legend, $tick);

        // Attach image back to agent in simulation state
        $agent['image_url'] = $legend->image_url;
    }

    protected function applyMutationPressure(LegendaryAgent $legend, int $tick): void
    {
        // Simple heuristic: if archetype is complex or agent is old, pressure increases
        $severity = rand(10, 80);
        $type = rand(0, 1) ? 'corruption' : 'ascension';
        
        $this->applyMutation->execute($legend, $type, $severity, $tick);
    }
}


