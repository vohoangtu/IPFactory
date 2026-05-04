<?php

namespace App\Modules\Narrative\Events;

use App\Modules\Narrative\Models\Chronicle;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * NarrativeGenerated: Fired when a new chronicle and its signals are processed.
 */
class NarrativeGenerated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $universeId,
        public readonly int $tick,
        public readonly Chronicle $chronicle,
        public readonly array $signals
    ) {}
}

