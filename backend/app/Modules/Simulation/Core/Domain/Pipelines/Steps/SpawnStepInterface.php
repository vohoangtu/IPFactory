<?php

namespace App\Modules\Simulation\Core\Domain\Pipelines\Steps;

use App\Modules\World\Models\World;
use App\Modules\World\Models\Universe;

/**
 * Interface cho các bước trong SpawnPipeline.
 */
interface SpawnStepInterface
{
    /**
     * Thực thi bước logic.
     * 
     * @param array $context Dữ liệu dùng chung giữa các bước.
     * @return array Context đã được cập nhật.
     */
    public function execute(array $context): array;
}

