<?php

namespace App\Modules\Simulation\Console\Commands;

use App\Modules\World\Models\Universe;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Actions\AdvanceSimulationAction;
use Illuminate\Console\Command;

class WorldosSimCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'worldos:sim {universe? : The ID of the universe} {--ticks=1 : Number of ticks to simulate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate ticks and view the resulting narrative chronicle.';

    /**
     * Execute the console command.
     */
    public function handle(AdvanceSimulationAction $advanceAction): int
    {
        $id = $this->argument('universe');
        $ticks = (int) $this->option('ticks');

        $universe = $id ? Universe::find($id) : Universe::where('status', 'active')->first();

        if (!$universe) {
            $this->error("No active universe found.");
            return 1;
        }

        $this->info(">>> Simulating {$universe->name} (ID: {$universe->id}) for {$ticks} ticks...");

        $response = $advanceAction->execute($universe->id, $ticks);

        if (!($response['ok'] ?? false)) {
            $this->error("Simulation failed: " . ($response['error'] ?? 'Unknown error'));
            return 1;
        }

        $universe->refresh();
        $this->success("Simulation successful. Current Tick: {$universe->current_tick}");

        // Fetch latest chronicle
        $chronicle = Chronicle::where('universe_id', $universe->id)
            ->latest()
            ->first();

        if ($chronicle) {
            $this->newLine();
            $this->info("=== LATEST CHRONICLE ===");
            $this->line("<options=bold>Type:</> {$chronicle->type}");
            $this->line("<options=bold>Ticks:</> {$chronicle->from_tick} -> {$chronicle->to_tick}");
            $this->newLine();
            $this->info($chronicle->content ?? "No content generated.");
            $this->newLine();
            $this->info("========================");
        } else {
            $this->warn("No chronicle generated for this pulse.");
        }

        // Display basic metrics
        $snapshot = UniverseSnapshot::where('universe_id', $universe->id)->latest()->first();
        if ($snapshot) {
            $this->newLine();
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Entropy', $universe->entropy],
                    ['Stability', $universe->stability_index],
                    ['Observation Load', $universe->observation_load],
                ]
            );
        }

        return 0;
    }

    private function success(string $message): void
    {
        $this->line("<info>SUCCESS</info> {$message}");
    }
}
