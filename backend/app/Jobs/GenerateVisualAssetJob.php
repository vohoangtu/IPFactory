<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Modules\Intelligence\Models\LegendaryAgent;
use App\Modules\Narrative\Models\Artifact;

class GenerateVisualAssetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120; // DALL-E might take time

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $universeId,
        public string $entityType, // 'celebrity' | 'artifact'
        public string $prompt,
        public int $entityId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $loomUrl = config('services.loom.url', 'http://narrative_loom:8001');

        try {
            $isPortrait = $this->entityType === 'celebrity';
            
            // 1. Gửi request sinh ảnh tới Narrative Loom Art Director
            $response = Http::timeout(60)->post($loomUrl . '/paint-asset', [
                'prompt' => $this->prompt,
                'is_portrait' => $isPortrait
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $imageUrl = $data['image_url'] ?? null;

                if ($imageUrl) {
                    // 2. Tải ảnh từ URL về local storage
                    $imageContent = Http::timeout(30)->get($imageUrl)->body();
                    $filename = "assets/{$this->entityType}_{$this->entityId}_" . Str::random(8) . ".png";
                    
                    Storage::disk('public')->put($filename, $imageContent);
                    $localUrl = Storage::url($filename);

                    $this->upsertAssetRecord($localUrl, $imageUrl, $filename);

                    Log::info("Visual Asset Generated: {$localUrl}");
                }
            } else {
                Log::error("NarrativeLoom failed to generate image: " . $response->body());
            }

        } catch (\Exception $e) {
            Log::error("GenerateVisualAssetJob Exception: " . $e->getMessage());
        }
    }

    private function upsertAssetRecord(string $localUrl, string $sourceUrl, string $storagePath): void
    {
        $payload = [
            'local_url' => $localUrl,
            'source_url' => $sourceUrl,
            'storage_path' => $storagePath,
            'generated_at' => now()->toISOString(),
            'prompt' => $this->prompt,
        ];

        if ($this->entityType === 'celebrity') {
            // Find matching LegendaryAgent rows deterministically rather than
            // mass-updating across both id and original_agent_id in a single
            // statement. The previous orWhere() pattern would update two
            // distinct rows in one UPDATE if both happened to match, silently
            // overwriting an unrelated image_url with the same value. Here
            // we query first, then update each row individually so the
            // intent is auditable in logs and the blast radius is bounded.
            $matches = LegendaryAgent::where('universe_id', $this->universeId)
                ->where(function ($query) {
                    $query->where('id', $this->entityId)
                        ->orWhere('original_agent_id', $this->entityId);
                })
                ->get();

            if ($matches->isEmpty()) {
                Log::warning('Visual Asset: no LegendaryAgent matched for upsert', [
                    'universe_id' => $this->universeId,
                    'entity_id' => $this->entityId,
                ]);
                return;
            }

            foreach ($matches as $agent) {
                $agent->forceFill(['image_url' => $localUrl])->save();
                Log::info('Visual Asset persisted to legendary_agents.image_url', [
                    'universe_id' => $this->universeId,
                    'legendary_agent_id' => $agent->id,
                    'matched_via' => $agent->id === $this->entityId ? 'id' : 'original_agent_id',
                    'local_url' => $localUrl,
                ]);
            }

            return;
        }

        if ($this->entityType === 'artifact') {
            $artifact = Artifact::where('universe_id', $this->universeId)->find($this->entityId);
            if ($artifact) {
                $metadata = $artifact->metadata ?? [];
                $metadata['visual_asset'] = $payload;
                $artifact->forceFill(['metadata' => $metadata])->save();

                Log::info('Visual Asset persisted to artifact metadata', [
                    'universe_id' => $this->universeId,
                    'artifact_id' => $this->entityId,
                    'local_url' => $localUrl,
                ]);
            }
        }
    }
}
