<?php

namespace App\Modules\Simulation\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ComposeEpochSoundtrackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Tên Epoch hiện tại
     */
    protected string $epochName;

    /**
     * Vibe/Chủ đề của epoch
     */
    protected string $coreTheme;

    /**
     * Create a new job instance.
     */
    public function __construct(string $epochName, string $coreTheme)
    {
        $this->epochName = $epochName;
        $this->coreTheme = $coreTheme;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("[AUDIO] Đang sinh nhạc Ambient cho Kỷ nguyên: " . $this->epochName);

        $url = env('NARRATIVE_LOOM_URL', 'http://narrative-loom:8000') . '/compose-track';

        try {
            $response = Http::timeout(60)->post($url, [
                'epoch_name' => $this->epochName,
                'core_theme' => $this->coreTheme
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $streamUrl = $data['stream_url'] ?? null;
                $style = $data['style'] ?? 'default';

                if ($streamUrl) {
                    Log::info("[AUDIO] Gen thành công url nhạc ({$style}): " . $streamUrl);
                    // Ở đây có thể kích hoạt Event báo cho Frontend biết âm thanh đã thay đổi
                    // Centrifugo Broadcast channel `global_universe` -> event `SoundtrackChanged`.
                    $this->broadcastSoundtrackChange($streamUrl, $this->epochName, $style);
                } else {
                    Log::warning("[AUDIO] Narrative Loom không trả về âm thanh.");
                }
            } else {
                Log::error("[AUDIO] Lỗi từ Narrative Loom: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("[AUDIO] Không kết nối được tới Narrative Loom: " . $e->getMessage());
        }
    }

    private function broadcastSoundtrackChange(string $url, string $epochName, string $style)
    {
        // Broadcast qua Centrifugo thông qua event giả lập
        event(new \App\Modules\Simulation\Events\SoundtrackChanged($url, $epochName, $style));
    }
}
