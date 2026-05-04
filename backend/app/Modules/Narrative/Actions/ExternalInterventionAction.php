<?php

namespace App\Modules\Narrative\Actions;

use App\Modules\World\Models\World;
use App\Modules\Narrative\Services\OmenIntegrationService;
use App\Modules\Narrative\Actions\CelestialEngineeringAction;
use Illuminate\Support\Facades\Log;

/**
 * ExternalInterventionAction: High-level 'Reality Leak' triggered by the Architect (§V18).
 */
class ExternalInterventionAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        protected CelestialEngineeringAction $engineering
    ) {}

    /**
     * Inject a manual Omen from the Architect's realm.
     */
    public function execute(int $worldId, string $type, string $description): void
    {
        Log::emergency("REALITY BREACH: The Architect has manually injected an Omen: {$type}");

        $payload = [
            'name' => "Sự Can Thiệp Từ Cõi Ngoài: " . $type,
            'omen_type' => 'ARCHITECT_INTERVENTION',
            'omen_description' => $description,
            'sci_impact' => ($type === 'blessing') ? 0.2 : -0.2,
            'entropy_impact' => ($type === 'blessing') ? -0.2 : 0.2,
        ];

        $this->engineering->executeMacro($worldId, 'macro_edict', $payload);
    }
}


