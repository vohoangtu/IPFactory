<?php

namespace App\Modules\Simulation\Core\Runtime;

use App\Modules\Simulation\Core\Runtime\Contracts\TickSchedulerInterface;
use Illuminate\Contracts\Config\Repository as Config;

final class TickScheduler implements TickSchedulerInterface
{
    private array $intervals;

    private array $order;

    public function __construct(Config $config)
    {
        $pipeline = $config->get('worldos.tick_pipeline', $this->defaultPipeline());
        $this->intervals = [];
        $this->order = [];
        foreach ($pipeline as $key => $entry) {
            $this->order[] = $key;
            $this->intervals[$key] = (int) ($entry['interval'] ?? 1);
        }
    }

    public function shouldRun(string $stageKey, int $tick): bool
    {
        $interval = $this->intervals[$stageKey] ?? 1;
        return $interval <= 0 || $tick % $interval === 0;
    }

    public function stageOrder(): array
    {
        return $this->order;
    }

    private function defaultPipeline(): array
    {
        return [
            'actor' => ['interval' => 1],
            'culture' => ['interval' => 1],
            'civilization' => ['interval' => 1],
            'economy' => ['interval' => 10],
            'politics' => ['interval' => 20],
            'war' => ['interval' => 50],
            'ecology' => ['interval' => 1],
            'meta' => ['interval' => 1],
        ];
    }
}
