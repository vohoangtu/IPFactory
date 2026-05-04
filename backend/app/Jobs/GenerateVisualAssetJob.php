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
use App\Modules\World\Models\Universe;

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

                    // TODO: Update Database record logic here if appropriate
                    // For now, we will log it. Next.js can fetch it if we broadcast an event.
                    Log::info("Visual Asset Generated: {$localUrl}");
                }
            } else {
                Log::error("NarrativeLoom failed to generate image: " . $response->body());
            }

        } catch (\Exception $e) {
            Log::error("GenerateVisualAssetJob Exception: " . $e->getMessage());
        }
    }
}
