<?php

namespace App\Modules\Simulation\Core\Runtime\RuleVM;

use App\Modules\Simulation\Models\RuleProposal;
use App\Modules\Simulation\Core\EngineRegistry;
use Illuminate\Support\Facades\Log;

/**
 * Deploy approved rule proposal: set version, engine_manifest_snapshot, deployed_at.
 */
final class DeployRuleProposalService
{
    public function __construct(
        private readonly EngineRegistry $registry
    ) {}

    public function deploy(RuleProposal $proposal): bool
    {
        if ($proposal->deployed_at !== null) {
            Log::warning('DeployRuleProposalService: proposal already deployed', ['id' => $proposal->id]);
            return false;
        }

        $version = $this->nextVersion();
        $manifest = $this->registry->getManifest();

        $proposal->update([
            'version' => $version,
            'deployed_at' => now(),
            'engine_manifest_snapshot' => $manifest,
        ]);

        Log::info('DeployRuleProposalService: rule deployed', [
            'proposal_id' => $proposal->id,
            'version' => $version,
            'universe_id' => $proposal->universe_id,
        ]);

        return true;
    }

    private function nextVersion(): string
    {
        $max = RuleProposal::whereNotNull('deployed_at')
            ->whereNotNull('version')
            ->orderByDesc('id')
            ->value('version');

        if ($max === null || $max === '') return 'v1';
        
        if (preg_match('/^v(\d+)$/', $max, $m)) {
            return 'v' . ((int) $m[1] + 1);
        }
        return 'v' . (RuleProposal::whereNotNull('deployed_at')->count() + 1);
    }
}

