<?php

namespace App\Modules\Simulation\Vocation\Services;

class ExecutionResult
{
    public bool $success = true;
    public string $message = '';
    public float $value = 0.0;
    public array $effects = [];
    public array $logs = [];

    public function addEffect(array $effect): void
    {
        $this->effects[] = $effect;
    }

    public function addLog(string $message): void
    {
        $this->logs[] = $message;
    }

    /**
     * Merge multiple results (important for chain reactions)
     */
    public function merge(self $other): void
    {
        $this->success = $this->success && $other->success;
        $this->value += $other->value;
        $this->effects = array_merge($this->effects, $other->effects);
        $this->logs = array_merge($this->logs, $other->logs);
    }
}
