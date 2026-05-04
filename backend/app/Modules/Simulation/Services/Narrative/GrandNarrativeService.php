<?php

namespace App\Modules\Simulation\Services\Narrative;

use App\Modules\World\Models\Universe;
use App\Modules\Knowledge\Models\Idea;
use App\Modules\World\Models\School;
use App\Modules\Institutions\Models\InstitutionalEntity;
use App\Modules\World\Models\Material;
use App\Modules\Intelligence\Services\Dashboard\StateMetricsService;

class GrandNarrativeService
{
    public function __construct(
        protected StateMetricsService $metricsService
    ) {}

    /**
     * Generate a grand narrative report for a universe.
     */
    public function generateReport(int $universeId): array
    {
        $macro = $this->metricsService->getMacroState($universeId);
        $universe = Universe::findOrFail($universeId);

        return [
            'age_name' => $this->identifyAge($macro),
            'summary' => $this->generateSummary($macro),
            'military' => $this->summarizeMilitary($macro),
            'culture' => $this->summarizeCulture($universeId, $macro),
            'technology' => $this->summarizeTech($universeId, $macro),
            'paradoxes' => $this->detectParadoxes($macro),
            'metrics' => $macro,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    protected function identifyAge(array $macro): string
    {
        $tech = $macro['tech'] ?? 0;
        $militarism = $macro['militarism'] ?? 0;
        $entropy = $macro['entropy'] ?? 0;

        if ($entropy > 0.8) return "Kỷ nguyên Hỗn Loạn (Age of Chaos)";
        if ($militarism > 0.7) return "Kỷ nguyên Chinh Phạt (Age of Conquest)";
        if ($tech > 0.8) return "Kỷ nguyên Siêu Việt (Age of Transcendence)";
        if ($tech > 0.4) return "Kỷ nguyên Khai Sáng (Age of Enlightenment)";
        
        return "Kỷ nguyên Sơ Khai (Dawn of Civilization)";
    }

    protected function generateSummary(array $macro): string
    {
        $stability = $macro['stability'] ?? 0;
        $innovation = $macro['innovation'] ?? 0;

        if ($stability < 0.3 && $innovation > 0.7) {
            return "Thế giới đang trải qua sự chuyển mình dữ dội. Trật tự cũ sụp đổ nhường chỗ cho những đột phá không tưởng.";
        }
        if ($stability > 0.8) {
            return "Văn minh đang trong giai đoạn cực thịnh và ổn định. Các thiết chế hoạt động nhịp nhàng, tạo nên một thời kỳ thái bình thịnh trị.";
        }
        
        return "Sự tiến hóa đang diễn ra theo quỹ đạo nhân quả tự nhiên, cân bằng giữa di sản và đổi mới.";
    }

    protected function summarizeMilitary(array $macro): array
    {
        $militarism = $macro['militarism'] ?? 0;
        $status = $militarism > 0.5 ? "Xung đột cao" : ($militarism > 0.2 ? "Căng thẳng âm ỉ" : "Hòa bình");
        
        return [
            'status' => $status,
            'intensity' => $militarism,
            'description' => "Hoạt động quân sự hiện tại đang ở mức " . strtolower($status) . ".",
        ];
    }

    protected function summarizeCulture(int $universeId, array $macro): array
    {
        $ideaCount = Idea::where('universe_id', $universeId)->count();
        $schoolCount = School::where('universe_id', $universeId)->count();
        $instCount = InstitutionalEntity::where('universe_id', $universeId)->where('entity_type', 'philosophy_school')->count();

        return [
            'idea_diversity' => $ideaCount,
            'established_schools' => $schoolCount,
            'leading_institutions' => $instCount,
            'spirituality' => $macro['spirituality'] ?? 0,
            'description' => "Văn hóa phát triển với {$ideaCount} tư tưởng cốt lõi và {$schoolCount} học phái đang hoạt động.",
        ];
    }

    protected function summarizeTech(int $universeId, array $macro): array
    {
        $activeMaterials = \App\Modules\Simulation\Models\MaterialInstance::where('universe_id', $universeId)
            ->where('lifecycle', Material::LIFECYCLE_ACTIVE)
            ->count();
            
        return [
            'material_complexity' => $activeMaterials,
            'innovation_potential' => $macro['innovation'] ?? 0,
            'tech_level' => $macro['tech'] ?? 0,
            'description' => "Nền tảng vật chất bao gồm {$activeMaterials} vật chất hoạt tính. Tiềm năng đổi mới được đánh giá ở mức " . round(($macro['innovation'] ?? 0) * 100) . "%.",
        ];
    }

    protected function detectParadoxes(array $macro): array
    {
        $paradoxes = [];
        $noise = $macro['noise'] ?? 0;
        $stability = $macro['stability'] ?? 0;

        if ($noise > 0.6 && $stability > 0.8) {
            $paradoxes[] = [
                'type' => 'Nghịch lý Ổn định (Stability Paradox)',
                'severity' => 'High',
                'description' => 'Dữ liệu cho thấy sự ổn định cực cao bất chấp nhiễu thông tin lớn. Có dấu hiệu của sự can thiệp hoặc che giấu nhân quả.'
            ];
        }

        if ($macro['tech'] > 0.9 && $macro['entropy'] > 0.9) {
            $paradoxes[] = [
                'type' => 'Entropy Siêu Việt (Transcendental Entropy)',
                'severity' => 'Critical',
                'description' => 'Sự sụp đổ cấu trúc diễn ra đồng thời với đỉnh cao công nghệ. Vũ trụ có thể đang tiến tới Omega Point.'
            ];
        }

        return $paradoxes;
    }
}

