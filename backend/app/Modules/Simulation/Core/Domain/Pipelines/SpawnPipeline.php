<?php

namespace App\Modules\Simulation\Core\Domain\Pipelines;

use App\Modules\World\Models\World;
use App\Modules\World\Models\Universe;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SpawnPipeline — Orchestrates cái quy trình khởi tạo Universe mới.
 * 
 * Thay thế cho God Method trong ImplicitOrchestratorService.
 */
class SpawnPipeline
{
    /** @var SpawnStepInterface[] */
    protected array $steps = [];

    public function __construct(iterable $steps)
    {
        foreach ($steps as $step) {
            $this->steps[] = $step;
        }
    }

    /**
     * Chạy quy trình khởi tạo.
     */
    public function run(World $world, ?int $parentUniverseId = null, ?int $sagaId = null, ?array $branchPayload = null): Universe
    {
        Log::info("SpawnPipeline: Starting genesis for world {$world->id}");

        $context = [
            'world' => $world,
            'parent_universe_id' => $parentUniverseId,
            'saga_id' => $sagaId,
            'branch_payload' => $branchPayload,
            'initial_state' => null,
            'genome' => null,
            'start_tick' => 0,
            'universe' => null,
        ];

        return DB::transaction(function () use ($context) {
            foreach ($this->steps as $step) {
                $context = $step->execute($context);
            }

            if (!$context['universe'] instanceof Universe) {
                throw new \RuntimeException("SpawnPipeline failed: Universe was not created.");
            }

            return $context['universe'];
        });
    }
}

