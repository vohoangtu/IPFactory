<?php

namespace App\Modules\Intelligence\Events;

use App\Modules\World\Models\Universe;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CollectiveUnconsciousShifted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Universe $universe,
        public readonly array $oldVector,
        public readonly array $newVector
    ) {}
}

