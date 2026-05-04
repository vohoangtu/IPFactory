<?php

namespace App\Modules\Narrative\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Narrative\Services\NarrativeAiService;

class ArchiveAncientEras extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'worldos:archive-ancient-eras {--days=7 : Số ngày tuổi của data cần archive}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gom các tick cũ (UniverseSnapshot) thành 1 Node LTM (Chronicle) và xóa data gốc để giải phóng DB.';

    /**
     * Execute the console command.
     */
    public function handle(NarrativeAiService $aiService)
    {
        $days = (int) $this->option('days');
        $cutoffDate = now()->subDays($days);
        
        $this->info("Bắt đầu nén dữ liệu cũ hơn ngày {$cutoffDate->toDateString()}...");

        $universes = Universe::all();

        foreach ($universes as $universe) {
            $this->archiveUniverse($universe, $cutoffDate, $aiService);
        }

        $this->info('Hoàn tất nén dữ liệu cổ đại.');
    }

    protected function archiveUniverse(Universe $universe, $cutoffDate, NarrativeAiService $aiService)
    {
        // 1. Get all snapshots before cutoff
        $oldSnapshotsQuery = UniverseSnapshot::where('universe_id', $universe->id)
            ->where('created_at', '<', $cutoffDate);
            
        $count = $oldSnapshotsQuery->count();
        if ($count === 0) {
            return;
        }

        $this->info("Universe {$universe->id}: Nén {$count} snapshots...");

        $firstSnapshot = $oldSnapshotsQuery->orderBy('tick', 'asc')->first();
        $lastSnapshot = $oldSnapshotsQuery->orderBy('tick', 'desc')->first();

        $fromTick = $firstSnapshot->tick;
        $toTick = $lastSnapshot->tick;

        // 2. Extract key metrics to summarize
        // In a real scenario, we might want to sample every 100th tick to feed into AI
        $prompt = "Hãy tóm tắt toàn bộ chiều dài lịch sử của Vũ trụ #{$universe->id} từ tick {$fromTick} đến {$toTick}. Bạn đang đóng vai trò là một kho lưu trữ Ký ức LTM (Long-Term Memory). Trả về một tóm tắt dạng Văn bia Cổ đại mô tả các sự kiện chính và vết sẹo còn sót lại.";
        
        // 3. Generate summary using NarrativeAiService
        $archiveContent = $aiService->generateSnippet($prompt) ?? "Dữ liệu cổ đại từ tick {$fromTick} đến {$toTick} đã rơi vào hư không (Fog of War).";

        // 4. Create an archival Chronicle (LTM Vector Node)
        $chronicle = Chronicle::create([
            'universe_id' => $universe->id,
            'from_tick' => $fromTick,
            'to_tick' => $toTick,
            'type' => 'ancient_archive',
            'content' => $archiveContent,
            'raw_payload' => [
                'action' => 'data_pruning',
                'description' => "Compressed {$count} raw snapshots into Archival Node."
            ],
            // 'embedding' => calculate vector... (done by observer/event)
        ]);

        // 5. Delete raw snapshots to save space
        $oldSnapshotsQuery->delete();
        
        $this->info("Universe {$universe->id}: Đã tạo Chronicle #{$chronicle->id} và xóa {$count} snapshots raw.");
    }
}

