<?php

namespace App\Modules\Simulation\Console\Commands;

use App\Modules\Simulation\Models\RuleProposal;
use App\Modules\Simulation\Services\RuleEngine\DeployRuleProposalService;
use Illuminate\Console\Command;

/**
 * Deploy a rule proposal (Doc §30): set version, engine_manifest_snapshot, deployed_at.
 * Use after sandbox test passes and proposal is approved.
 */
class DeployRuleProposalCommand extends Command
{
    protected $signature = 'worldos:deploy-rule-proposal {id : Rule proposal ID}';

    protected $description = 'Deploy an approved rule proposal (version + manifest snapshot)';

    public function handle(DeployRuleProposalService $deployer): int
    {
        $id = (int) $this->argument('id');
        $proposal = RuleProposal::find($id);
        if (! $proposal) {
            $this->error("Rule proposal {$id} not found.");
            return 1;
        }
        if ($proposal->deployed_at !== null) {
            $this->warn("Proposal {$id} is already deployed at {$proposal->deployed_at}.");
            return 0;
        }
        if ($deployer->deploy($proposal)) {
            $proposal->refresh();
            $this->info("Deployed proposal {$id} as version {$proposal->version}.");
            return 0;
        }
        $this->error('Deploy failed.');
        return 1;
    }
}




