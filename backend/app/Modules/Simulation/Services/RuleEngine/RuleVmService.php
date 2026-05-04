<?php

namespace App\Modules\Simulation\Services\RuleEngine;

use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService as StandardRuleVmService;

/**
 * Backward compatibility shim for RuleVmService.
 * All legacy engines still point here, but logic is redirected to the Core implementation.
 */
class RuleVmService extends StandardRuleVmService
{
    // No extra logic needed, just serves as a namespace redirect.
}
