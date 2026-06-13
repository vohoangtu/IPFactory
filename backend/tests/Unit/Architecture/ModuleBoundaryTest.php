<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * Architecture guardrail (P0-6) — chặn coupling concrete CHÉO MODULE ở TẦNG DOMAIN.
 *
 * Quy tắc DDD của dự án: module chỉ giao tiếp qua interface trong app/Contracts/.
 * Import Service/Action/Repository CỤ THỂ của module khác = "coupling nguy hiểm".
 *
 * PHẠM VI = tầng domain. Entry point (Http/Controllers, Console/Commands) là COMPOSITION ROOT
 * — nơi hợp lệ để wire class cụ thể — nên được MIỄN TRỪ. Coupling cần triệt là giữa các
 * Service/Action/Repository domain với nhau (thứ tạo nên lõi rối + circular dependency).
 *
 * RATCHET (chỉ được giảm):
 *  - BASELINE = số coupling domain hiện tại theo cặp (from→to), đo 2026-06-13 SAU khi phá 4 cycle.
 *  - Thêm coupling mới (cặp mới / vượt ngưỡng) → FAIL.
 *  - Khi refactor giảm tiếp → hạ số. Mục tiêu cuối: mọi cặp về 0.
 *
 * TRẠNG THÁI: ở tầng domain KHÔNG còn cặp song hướng → đồ thị module acyclic
 * (đã phá Intel⇄Inst, Sim⇄Intel, Narr⇄Intel, Sim⇄Narr). Các cặp dưới đều một chiều.
 *
 * Test thuần tĩnh (quét file), không boot app / không cần DB.
 */
final class ModuleBoundaryTest extends TestCase
{
    /** Ngưỡng tối đa mỗi cặp "Owner->Target" ở tầng domain. CHỈ ĐƯỢC GIẢM. */
    private const BASELINE = [
        'Simulation->Narrative'      => 44,
        'Simulation->Intelligence'   => 26,
        'Simulation->Institutions'   => 17,
        'Narrative->Intelligence'    => 11,
        'WorldOS->Simulation'        => 6,
        'Simulation->World'          => 3,
        'WorldOS->Intelligence'      => 2,
        'Simulation->Economics'      => 2,
        'Narrative->Institutions'    => 2,
        'Institutions->Intelligence' => 2,
        'Simulation->Psychology'     => 1,
        'Simulation->Geography'      => 1,
        'Knowledge->Simulation'      => 1,
        'Intelligence->World'        => 1,
        // Các back-edge đã triệt (= 0): Intelligence->Simulation, Intelligence->Institutions,
        // Intelligence->Narrative, Narrative->Simulation — KHÔNG thêm lại.
    ];

    public function test_no_new_cross_module_service_coupling(): void
    {
        $current = $this->scanDangerousCrossModuleImports();

        $violations = [];
        foreach ($current as $pair => $count) {
            $allowed = self::BASELINE[$pair] ?? 0;
            if ($count > $allowed) {
                $violations[] = sprintf(
                    '%s = %d (baseline %d) → có import Service/Action/Repository chéo module MỚI ở tầng domain. Hãy dùng interface trong app/Contracts/.',
                    $pair,
                    $count,
                    $allowed
                );
            }
        }

        $this->assertSame(
            [],
            $violations,
            "Phát hiện coupling concrete chéo module vượt baseline:\n" . implode("\n", $violations)
        );
    }

    /** @return array<string,int> map "Owner->Target" => số lần import (đã loại entry point) */
    private function scanDangerousCrossModuleImports(): array
    {
        $base = realpath(__DIR__ . '/../../../app/Modules');
        $this->assertNotFalse($base, 'Không tìm thấy app/Modules');

        $sep = DIRECTORY_SEPARATOR;
        $counts = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $relative = substr($file->getPathname(), strlen($base) + 1);

            // Miễn trừ entry point / composition root: Http/Controllers, Console/Commands.
            if (str_contains($relative, $sep . 'Http' . $sep) || str_contains($relative, $sep . 'Console' . $sep)) {
                continue;
            }

            $owner = explode($sep, $relative)[0];

            $source = file_get_contents($file->getPathname());
            if (! preg_match_all(
                '/^use\s+App\\\\Modules\\\\(\w+)\\\\(Services|Actions|Repositories)\\\\/m',
                $source,
                $matches
            )) {
                continue;
            }

            foreach ($matches[1] as $target) {
                if ($target !== $owner) {
                    $key = $owner . '->' . $target;
                    $counts[$key] = ($counts[$key] ?? 0) + 1;
                }
            }
        }

        return $counts;
    }
}
