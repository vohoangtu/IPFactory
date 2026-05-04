<?php

namespace App\Modules\Simulation\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\Simulation\Actions\AdvanceSimulationAction;
use App\Modules\Simulation\Core\Runtime\State\WorldState;

class SimulationRunner extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'worldos:sim {universe=1} {--ticks=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kích hoạt mô phỏng và hiển thị số liệu chi tiết (CFT & Zone Pressures)';

    /**
     * Execute the console command.
     */
    public function handle(AdvanceSimulationAction $action)
    {
        $universeId = (int) $this->argument('universe');
        $ticks = (int) $this->option('ticks');

        $this->info("🚀 Đang khởi chạy mô phỏng cho Universe #{$universeId} ($ticks ticks)...");

        $result = $action->execute($universeId, $ticks);

        if (!($result['ok'] ?? false)) {
            $this->error("❌ Lỗi mô phỏng: " . ($result['error_message'] ?? 'Không rõ nguyên nhân'));
            return 1;
        }

        $snapshot = $result['snapshot'] ?? [];
        $tick = $result['tick'] ?? 0;
        $stateVector = $snapshot['state_vector'] ?? [];
        
        $this->newLine();
        $this->info("✅ Hoàn tất Tick #{$tick}");
        $this->newLine();

        // 1. Hiển thị 10 trường CFT (Civilization Field Theory)
        $fields = $stateVector['fields'] ?? [];
        if (!empty($fields)) {
            $this->comment("📊 [Civilization Field Theory - 10D]");
            $headers = ['Survival', 'Power', 'Wealth', 'Knowledge', 'Meaning', 'Authority', 'Fear', 'Order', 'Entropy', 'Resonance'];
            $data = [[
                $fields['survival'] ?? 'N/A',
                $fields['power'] ?? 'N/A',
                $fields['wealth'] ?? 'N/A',
                $fields['knowledge'] ?? 'N/A',
                $fields['meaning'] ?? 'N/A',
                $fields['authority'] ?? 'N/A',
                $fields['fear'] ?? 'N/A',
                $fields['order'] ?? 'N/A',
                $fields['entropy'] ?? 'N/A',
                $fields['resonance'] ?? 'N/A',
            ]];
            $this->table($headers, $data);
        }

        // 2. Hiển thị Zone Pressures (Lấy ngẫu nhiên 3 Zone đầu tiên nếu có)
        $zones = $stateVector['zones'] ?? [];
        if (!empty($zones)) {
            $this->newLine();
            $this->comment("📍 [Zone Pressures - Top 3 Zones]");
            $zoneHeaders = ['Zone ID', 'Name', 'War', 'Econ', 'Rel', 'Mig', 'Inn'];
            $zoneRows = [];
            
            $count = 0;
            foreach ($zones as $id => $zone) {
                if ($count >= 3) break;
                
                $zState = $zone['state'] ?? [];
                $zoneRows[] = [
                    $id,
                    $zone['name'] ?? 'Unknown',
                    $zState['war_pressure'] ?? 0,
                    $zState['economic_pressure'] ?? 0,
                    $zState['religious_pressure'] ?? 0,
                    $zState['migration_pressure'] ?? 0,
                    $zState['innovation_pressure'] ?? 0,
                ];
                $count++;
            }
            $this->table($zoneHeaders, $zoneRows);
        }

        // 3. Hiển thị Engine Health
        $metrics = $snapshot['metrics'] ?? [];
        if (!empty($metrics)) {
            $this->newLine();
            $this->comment("⚙️ [Engine Performance]");
            $this->line("Health Score: <fg=green>{$metrics['engine_health']}</>");
            $this->line("Last Tick Time: <fg=yellow>{$metrics['last_tick_ms']}ms</>");
        }

        $this->newLine();
        return 0;
    }
}
