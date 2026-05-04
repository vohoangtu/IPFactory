<?php

namespace App\Modules\Intelligence\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\World\Models\Universe;
use App\Modules\Intelligence\Services\AI\WorldAdvisorService;

class RunAiAnalysis extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'worldos:run-ai-analysis {--universe= : ID của một Universe cụ thể. Nếu bỏ trống, chạy cho tất cả Universe đang Active.}';

    /**
     * The console command description.
     */
    protected $description = 'Chạy chu kỳ AI Support: phân tích pattern, đề xuất Axiom, sinh Material mới, lưu bộ nhớ dài hạn.';

    /**
     * Execute the console command.
     */
    public function handle(WorldAdvisorService $advisor)
    {
        $universeId = $this->option('universe');

        if ($universeId) {
            $universes = Universe::where('id', $universeId)->get();
        } else {
            $universes = Universe::where('status', '!=', 'archived')->get();
        }


        if ($universes->isEmpty()) {
            $this->warn('Không tìm thấy Universe nào đang hoạt động.');
            return;
        }

        $this->info("Bắt đầu AI Analysis cho {$universes->count()} Universe...");

        foreach ($universes as $universe) {
            $this->info("Xử lý Universe #{$universe->id}: {$universe->name}");

            try {
                $result = $advisor->advise($universe);
                
                // Report results
                $this->line("  → Pattern: " . ($result['pattern_analysis']['suggestion'] ?? 'N/A'));
                
                if (!empty($result['discovered_axiom'])) {
                    $axiom = $result['discovered_axiom'];
                    $this->line("  → Axiom mới: [{$axiom['axiom_key']}] (confidence: {$axiom['confidence']})");
                }

                if (!empty($result['synthesized_material'])) {
                    $mat = $result['synthesized_material'];
                    $this->line("  → Material mới: [{$mat['name']}] ({$mat['ontology']})");
                }

                $this->line("  → Advisory: " . substr($result['advisory_text'] ?? '', 0, 120) . '...');
            } catch (\Throwable $e) {
                $this->error("  ✗ Lỗi Universe #{$universe->id}: " . $e->getMessage());
            }
        }

        $this->info('Hoàn tất AI Analysis.');
    }
}

