<?php

namespace App\Modules\Simulation\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SocialEngineClient
{
    protected string $baseUrl;
    protected int $defaultAgentsCount;

    public function __construct()
    {
        // Trỏ vào cổng 5001 của Python Social Engine (FastAPI mới tạo)
        $this->baseUrl = config('services.social_engine.url', 'http://127.0.0.1:5001/api/v1');
        
        // Mặc định là 10 (đỡ tốn token)
        $this->defaultAgentsCount = config('services.social_engine.agents_count', 10);
    }

    /**
     * Ném một Event vào Social Engine để chạy phản ứng đám đông.
     * Cung cấp "Vỏ Bối Cảnh" rất dày.
     */
    public function spawnCrisisSwarm(string $eventName, string $eventDescription, array $worldState): bool
    {
        // Trích xuất bối cảnh hoặc dùng mặc định nếu WorldOS chưa cấp đủ
        $era = $worldState['era'] ?? 'Unknown Era';
        $techLevel = $worldState['tech_level'] ?? 'Unknown Tech Level';
        $socialStructure = $worldState['social_structure'] ?? 'Unknown Structure';
        $communication = $worldState['communication_method'] ?? 'Unknown means of communication';

        $payload = [
            'era' => $era,
            'tech_level' => $techLevel,
            'social_structure' => $socialStructure,
            'communication_method' => $communication,
            'event_trigger' => "[$eventName] $eventDescription",
            'agents_count' => $this->defaultAgentsCount
        ];

        // Nếu hệ thống đang thiếu kinh phí (API Key rỗng hoặc cố tình Mock)
        if (config('services.social_engine.mock_mode', false)) {
            Log::info("🪙 [SocialEngine] (MOCK MODE) WorldOS đang thiếu ngân sách Token! Giả lập thành công Swarm Event nhưng bỏ qua gọi AI.", ['payload' => $payload]);
            return true;
        }

        Log::info("🌍 [SocialEngine] Bắn Event Mô phỏng Căng thẳng", ['payload' => $payload]);

        try {
            $response = Http::timeout(10)->post("{$this->baseUrl}/swarm/spawn", $payload);

            if ($response->successful()) {
                $data = $response->json();
                Log::info("✅ [SocialEngine] Khởi động Swarm thành công.", ['task_id' => $data['task_id'] ?? '']);
                return true;
            }

            Log::error("❌ [SocialEngine] Lỗi khi gọi API", ['status' => $response->status(), 'body' => $response->body()]);
            return false;

        } catch (\Exception $e) {
            Log::error("❌ [SocialEngine] Lỗi Kết Nối tới Social Engine: " . $e->getMessage());
            return false;
        }
    }
}
