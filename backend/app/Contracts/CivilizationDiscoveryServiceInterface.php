<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Hợp đồng cho CivilizationDiscoveryService (module Simulation).
 *
 * Đặt ở app/Contracts/ (global) để entry point ở module khác — vd
 * Intelligence\Console\Commands\DiscoveryRunGenerationCommand — phụ thuộc interface
 * thay vì class cụ thể của Simulation, phá nốt cycle Simulation ⇄ Intelligence (P0-6).
 */
interface CivilizationDiscoveryServiceInterface
{
    /**
     * Chạy một thế hệ GA: đánh giá fitness, chọn top-k, (tùy chọn) crossover + mutate.
     *
     * @param int[] $universeIds
     * @return array{evaluated: array<int,float>, selected: int[], next_generation: int[]}
     */
    public function runGeneration(array $universeIds): array;
}
