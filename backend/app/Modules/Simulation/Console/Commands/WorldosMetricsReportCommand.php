<?php

namespace App\Modules\Simulation\Console\Commands;

use App\Modules\World\Models\Universe;
use App\Modules\Intelligence\Services\BiologyMetricsService;
use App\Modules\Intelligence\Services\EcosystemMetricsService;
use Illuminate\Console\Command;

/**
 * Báo cáo số liệu hiện tại của universe(s) và đánh giá mức độ phù hợp
 * (instability, resource stress, ecological collapse, biology metrics).
 */
class WorldosMetricsReportCommand extends Command
{
    protected $signature = 'worldos:metrics-report
                            {universe? : Universe ID — bỏ trống thì báo cáo tất cả universe active}
                            {--json : Xuất JSON thay vì bảng}
                            {--check : Chỉ in đánh giá thích hợp/không (ngắn gọn)}';

    protected $description = 'Báo cáo số liệu ecosystem/biology của universe và đánh giá mức độ phù hợp (nguy cơ collapse, stress)';

    public function handle(
        BiologyMetricsService $biologyMetrics,
        EcosystemMetricsService $ecosystemMetrics
    ): int {
        $universeId = $this->argument('universe');
        $asJson = (bool) $this->option('json');
        $checkOnly = (bool) $this->option('check');

        $query = Universe::query();
        if ($universeId !== null && $universeId !== '') {
            $query->where('id', (int) $universeId);
        } else {
            $query->whereIn('status', ['active', 'running']);
        }
        $universes = $query->get();

        if ($universes->isEmpty()) {
            $this->warn('Không tìm thấy universe nào (hoặc ID không hợp lệ).');
            return 1;
        }

        $threshold = (float) config('worldos.intelligence.ecological_collapse_instability_threshold', 0.7);
        $rows = [];
        $verdicts = [];

        foreach ($universes as $universe) {
            $bio = $biologyMetrics->forUniverse($universe->id);
            $eco = $ecosystemMetrics->forUniverse($universe);
            $sv = is_array($universe->state_vector) ? $universe->state_vector : (json_decode($universe->state_vector ?? '{}', true) ?? []);
            $collapse = $sv['ecological_collapse'] ?? [];
            $civilization = $sv['civilization'] ?? [];

            $currentTick = (int) ($universe->current_tick ?? 0);
            $collapseActive = !empty($collapse['active']);
            $collapseType = $collapse['type'] ?? null;
            $collapseUntil = $collapse['until_tick'] ?? null;

            $verdict = $this->evaluateVerdict($eco, $collapseActive, $threshold);

            $verdicts[] = [
                'universe_id' => $universe->id,
                'name' => $universe->name ?? (string) $universe->id,
                'verdict' => $verdict['status'],
                'message' => $verdict['message'],
            ];

            $rows[] = [
                $universe->id,
                $universe->name ?? '-',
                $currentTick,
                $bio['total_alive'],
                $bio['species_count'],
                round($bio['avg_energy'] ?? 0, 2),
                $bio['starving_count'] ?? 0,
                round($eco['resource_stress'] ?? 0, 4),
                round($eco['instability_score'] ?? 0, 4),
                $collapseActive ? 'Có' : 'Không',
                $collapseType ?? '-',
                $collapseUntil ?? '-',
                $verdict['status'],
            ];
        }

        if ($checkOnly) {
            foreach ($verdicts as $v) {
                $this->line(sprintf('[Universe %s] %s — %s', $v['universe_id'], $v['verdict'], $v['message']));
            }
            return 0;
        }

        if ($asJson) {
            $output = [];
            foreach ($universes as $i => $universe) {
                $bio = $biologyMetrics->forUniverse($universe->id);
                $eco = $ecosystemMetrics->forUniverse($universe);
                $sv = is_array($universe->state_vector) ? $universe->state_vector : (json_decode($universe->state_vector ?? '{}', true) ?? []);
                $collapse = $sv['ecological_collapse'] ?? [];
                $output[] = [
                    'universe_id' => $universe->id,
                    'name' => $universe->name ?? null,
                    'current_tick' => $universe->current_tick ?? 0,
                    'biology' => $bio,
                    'ecosystem' => $eco,
                    'ecological_collapse_active' => !empty($collapse['active']),
                    'ecological_collapse' => $collapse,
                    'verdict' => $verdicts[$i] ?? null,
                ];
            }
            $this->line(json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return 0;
        }

        $this->info('Báo cáo số liệu WorldOS (ngưỡng instability collapse: ' . $threshold . ')');
        $this->table(
            [
                'ID',
                'Tên',
                'Tick',
                'Dân số',
                'Số loài',
                'Năng lượng TB',
                'Đói',
                'Resource stress',
                'Instability',
                'Đang collapse',
                'Loại collapse',
                'Đến tick',
                'Đánh giá',
            ],
            $rows
        );

        $this->newLine();
        $this->info('Chú thích: Instability >= ' . $threshold . ' → có thể kích hoạt ecological collapse tại các tick chia hết cho interval (mặc định 50).');
        foreach ($verdicts as $v) {
            $color = $v['verdict'] === 'OK' ? 'info' : ($v['verdict'] === 'Cảnh báo' ? 'comment' : 'error');
            $this->line(sprintf('  Universe %s: %s — %s', $v['universe_id'], $v['verdict'], $v['message']), $color);
        }

        return 0;
    }

    /**
     * @return array{status: string, message: string}
     */
    private function evaluateVerdict(array $eco, bool $collapseActive, float $threshold): array
    {
        $instability = (float) ($eco['instability_score'] ?? 0);
        $stress = (float) ($eco['resource_stress'] ?? 0);
        $population = (int) ($eco['total_population'] ?? 0);

        if ($collapseActive) {
            return [
                'status' => 'Đang collapse',
                'message' => 'Universe đang trong giai đoạn ecological collapse; hồi phục sau until_tick.',
            ];
        }

        if ($instability >= $threshold) {
            return [
                'status' => 'Rủi ro',
                'message' => sprintf('Instability (%.2f) >= ngưỡng (%.2f) — lần kiểm tra tiếp theo có thể trigger collapse.', $instability, $threshold),
            ];
        }

        if ($stress >= 0.8) {
            return [
                'status' => 'Cảnh báo',
                'message' => sprintf('Resource stress cao (%.2f); nên tăng food/resources hoặc giảm population.', $stress),
            ];
        }

        if ($population === 0) {
            return [
                'status' => 'Không dân',
                'message' => 'Không có actor sống; metrics chỉ phản ánh zones.',
            ];
        }

        return [
            'status' => 'OK',
            'message' => sprintf('Instability (%.2f) dưới ngưỡng; số liệu trong khoảng chấp nhận được.', $instability),
        ];
    }
}

