<?php

namespace App\Modules\Narrative\Console\Commands;

use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Narrative\Services\NarrativeLoomService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WeaveNarrativesCommand extends Command
{
    protected $signature = 'worldos:weave-narratives
                            {universe_id? : Specify the universe ID to weave}
                            {--limit=50 : Maximum number of chronicles to process at once}
                            {--batched : Aggregate by universe+tick and use 1 LLM call per group (scales better)}';

    protected $description = 'Trigger Narrative Loom to weave raw events into literary prose through AiGateway runtime routing.';

    public function __construct(
        protected NarrativeLoomService $narrativeLoomService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $universeIdInput = $this->argument('universe_id');

        $query = Chronicle::whereNull('content')->whereNotNull('raw_payload');
        if ($universeIdInput) {
            $query->where('universe_id', $universeIdInput);
        }

        $chronicles = $query->orderBy('tick', 'asc')->limit($limit)->get();

        if ($chronicles->isEmpty()) {
            $this->info('No raw chronicles found to weave.');
            return self::SUCCESS;
        }

        $grouped = $chronicles->groupBy('universe_id');

        foreach ($grouped as $universeId => $group) {
            $minTick = $group->min('tick');
            $maxTick = $group->max('tick');
            $ids = $group->pluck('id')->toArray();

            $this->info("Triggering Narrative Loom for Universe {$universeId} (Ticks: {$minTick} - {$maxTick})...");

            try {
                $result = $this->narrativeLoomService->weave((int) $universeId, (int) $minTick, (int) $maxTick);

                if (($result['ok'] ?? true) === false || isset($result['error'])) {
                    throw new \RuntimeException((string) ($result['error'] ?? 'Narrative Loom returned an error.'));
                }

                $prose = $result['final_prose'] ?? 'No prose generated.';
                $this->info('Successfully woven! Prose length: ' . strlen($prose));

                DB::transaction(function () use ($ids, $prose, $universeId, $minTick, $result): void {
                    $firstId = array_shift($ids);
                    Chronicle::where('id', $firstId)->update(['content' => $prose]);

                    if (count($ids) > 0) {
                        Chronicle::whereIn('id', $ids)->update(['content' => '[Merged into Loom Output]']);
                    }

                    \App\Modules\Narrative\Models\Narrative::create([
                        'universe_id' => $universeId,
                        'tick_born' => $minTick,
                        'story' => $prose,
                        'virality' => 0.8,
                        'distortion' => 0.2,
                        'is_active' => true,
                        'news_headline' => $result['news_headline'] ?? 'Breaking News from Universe ' . $universeId,
                        'news_slogan' => $result['news_slogan'] ?? 'The story blooms...',
                        'vfx_config' => $result['vfx_config'] ?? [],
                        'tags' => ['loom_output', 'multiverse_news'],
                    ]);
                });
            } catch (\Throwable $e) {
                Log::error("Failed to call Narrative Loom for Universe {$universeId}: " . $e->getMessage());
                $this->error('Narrative Loom Error: ' . $e->getMessage());
            }
        }

        $this->info('Finished weaving. Batches processed successfully.');
        return self::SUCCESS;
    }
}
