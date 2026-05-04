<?php

namespace App\Modules\Narrative\Console\Commands;

use App\Modules\World\Models\Universe;
use App\Modules\Narrative\Services\HistorianAgentService;
use Illuminate\Console\Command;

class GenerateHistorianCommand extends Command
{
    protected $signature = 'worldos:historian-generate
                            {universe_id : Universe ID}
                            {--type=history_volume : Output type: history_volume, historian_essay, philosophy_treatise}
                            {--from= : From tick (optional)}
                            {--to= : To tick (optional)}
                            {--theme=general : Theme for the narrative}
                            {--actor= : Actor ID to focus on (optional)}';

    protected $description = 'Narrative v2: Generate a history volume, essay, or philosophy treatise via AI Historian from Historical Facts + timeline.';

    public function handle(HistorianAgentService $historian): int
    {
        $universeId = (int) $this->argument('universe_id');
        $universe = Universe::find($universeId);
        if (! $universe) {
            $this->error("Universe {$universeId} not found.");
            return self::FAILURE;
        }

        $outputType = $this->option('type');
        if (! in_array($outputType, ['history_volume', 'historian_essay', 'philosophy_treatise'], true)) {
            $outputType = 'history_volume';
        }

        $criteria = [
            'theme' => $this->option('theme'),
        ];
        if ($this->option('from') !== null && $this->option('from') !== '') {
            $criteria['from_tick'] = (int) $this->option('from');
        }
        if ($this->option('to') !== null && $this->option('to') !== '') {
            $criteria['to_tick'] = (int) $this->option('to');
        }
        if ($this->option('actor') !== null && $this->option('actor') !== '') {
            $criteria['actor_id'] = (int) $this->option('actor');
        }

        $this->info("Generating {$outputType} for universe {$universe->name} (id={$universeId})...");

        $chronicle = $historian->generateHistory($universe, $outputType, $criteria);
        if (! $chronicle) {
            $this->error('Historian generation failed or LLM unavailable.');
            return self::FAILURE;
        }

        $this->info("Created Chronicle #{$chronicle->id} (type={$chronicle->type}, ticks {$chronicle->from_tick}–{$chronicle->to_tick}).");
        $this->line(substr($chronicle->content ?? '', 0, 500) . (strlen($chronicle->content ?? '') > 500 ? '...' : ''));

        return self::SUCCESS;
    }
}

