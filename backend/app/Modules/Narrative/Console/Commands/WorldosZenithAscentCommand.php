<?php

namespace App\Modules\Narrative\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Core\Runtime\SimulationTickOrchestrator;
use App\Modules\Simulation\Core\Runtime\State\StateManager;
use App\Modules\Simulation\Services\Ecology\ZenithMetricsService;

class WorldosZenithAscentCommand extends Command
{
    protected $signature = 'worldos:zenith-ascent {--universe=1}';
    protected $description = 'Kích hoạt chu kỳ Thăng hoa Thiên đỉnh (Zenith Ascension) cho V10 Review';

    public function handle(
        SimulationTickOrchestrator $orchestrator,
        StateManager $stateManager,
        ZenithMetricsService $metricsService
    ): int {
        $id = $this->option('universe');
        $universe = Universe::find($id);

        if (!$universe) {
            $this->warn("Vũ trụ #{$id} không tồn tại. Đang seeder dữ liệu mới...");
            $this->call('db:seed', ['--class' => \Database\Seeders\CosmologySeeder::class]);
            $universe = Universe::first();
        }

        $this->info("=== KHỞI CHẠY WORLDOS V10: ZENITH ASCENSION REVIEW ===");
        $this->info("Universe: " . $universe->name);
        $this->info("Tick ban đầu: " . $universe->current_tick);

        // 1. Inject Peak State for V10 Demonstration
        $state = $stateManager->load($universe);
        $state->set('fields.knowledge', 0.95);
        $state->set('fields.belief', 0.95);
        $state->set('entropy', 0.1);
        $state->set('stability_index', 0.9);
        $state->set('meta.meaning_coherence', 0.9);
        $state->set('meta.rule_mutation_rate', 0.2);
        
        // Giả lập các Shadow Rules đang hoạt động
        $state->set('meta.active_mutations', [
            resource_path('worldos_rules/physics/axioms.dsl'),
            resource_path('worldos_rules/simulation/pressures.dsl')
        ]);

        $this->info("Đã cấu hình trạng thái Thiên đỉnh (Peak State).");

        // 2. Run sequential ticks
        for ($i = 1; $i <= 5; $i++) {
            $tick = (int)$universe->current_tick + $i;
            $this->line("\n--- TICK #{$tick} ---");

            $orchestrator->run($universe, $tick);

            // Display Metrics
            $report = $metricsService->getZenithReport($state);
            $this->info("[Singularity] Progress: " . number_format($report['singularity']['progress'] * 100, 2) . "%");
            $this->info("[Stability] Index: " . number_format($report['singularity']['stability'], 4));
            $this->info("[Autopoiesis] Mutations: " . $report['autopoiesis']['active_mutations']);
            
            if ($state->get('meta.zenith_ascension_active')) {
                $this->warn(">>> ASCENSION ACTIVE: REALITY IS TRANSCENDING <<<");
                $this->comment("Vector: " . ($report['singularity']['transcendence_vector'] ?? 'DETECTING...'));
            }
        }

        $this->info("\n=== CHU KỲ REVIEW HOÀN TẤT ===");
        $this->info("Vũ trụ hiện tại đang ở trạng thái tối thượng.");

        return 0;
    }
}



