<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Core\Domain;

use App\Modules\Simulation\Enums\SimulationPhase;

/**
 * PhaseExecutionResult — Collects EngineResult instances from all engines
 * that executed within a single simulation phase.
 */
final class PhaseExecutionResult
{
    /** @var EngineResult[] */
    private array $engineResults = [];

    /** @var string[] Engine names that were skipped. */
    private array $skippedEngines = [];

    private float $durationMs = 0;

    public function __construct(
        public readonly SimulationPhase $phase,
    ) {
    }

    public function addEngineResult(string $engineName, EngineResult $result): void
    {
        $this->engineResults[$engineName] = $result;

        if ($result->skipped) {
            $this->skippedEngines[] = $engineName;
        }

        $this->durationMs += $result->getDurationMs();
    }

    /**
     * @return EngineResult[]
     */
    public function getEngineResults(): array
    {
        return $this->engineResults;
    }

    /**
     * @return string[]
     */
    public function getSkippedEngines(): array
    {
        return $this->skippedEngines;
    }

    public function getDurationMs(): float
    {
        return $this->durationMs;
    }

    public function getEngineCount(): int
    {
        return count($this->engineResults);
    }
}
