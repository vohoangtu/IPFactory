<?php
namespace App\Modules\Simulation\Core\Engines;

class EngineResult {
    public function __construct(
        public array $stateChanges = [],
        public array $events = [],
        public array $metrics = [],
        public array $causalLinks = []
    ) {}

    public static function empty(): self
    {
        return new self();
    }
}
