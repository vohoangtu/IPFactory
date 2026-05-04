<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\World\Models\Universe;
use App\Modules\Intelligence\Models\AgentConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Phase 15: AI-Driven Policy Generation for Factions/Macro Agents
 */
class MacroAgentDecisionService
{
    public function __construct(
        protected \App\Modules\Intelligence\Services\AI\AiGateway $aiGateway
    ) {}

    public function generateEdict(Universe $universe, array $agent, object $leader): ?array
    {
        // Prepare the Prompt
        $traits = implode(', ', array_keys($leader->traits ?? ['Tham vọng' => 1]));
        $factionType = $agent['type'] ?? 'unknown';
        $entropy = $universe->entropy ?? 0.5;

        $systemPersona = "Bạn là mô phỏng AI của lãnh tụ một phe phái (Faction) trong một thế giới mô phỏng. Trả về đúng định dạng JSON cơ bản.";

        $prompt = <<<EOT
Bạn là {$leader->name}, lãnh đạo của phe phái loại [{$factionType}].
Tính cách cốt lõi của bạn: {$traits}.
Mức phân rã (Entropy) của thế giới hiện tại: {$entropy}. 
(Entropy cao > 0.8 là khủng hoảng sinh tồn, chiến tranh. Entropy thấp < 0.3 là trật tự, thái bình).

Dựa vào tình hình hiện tại, hãy ban hành một Sắc Lệnh (Edict) để thay đổi hoạt động của phe phái. Định dạng trả về BẮT BUỘC LÀ JSON:
{
  "edict_name": "Tên sắc lệnh (Ví dụ: Thiết Luật Thời Chiến)",
  "narrative_reason": "Giải thích ngắn gọn 1 câu tại sao lại ra quyết định này dưới góc độ Roleplay",
  "policy_focus": "Một trong các từ khóa: WAR, AGRICULTURE, TRADE, RELIGION, KNOWLEDGE, MYSTICISM",
  "drift_target": {
    "power": 0.8,
    "order": 0.7
  }
}
Lưu ý "drift_target" là mốc xoay chuyển văn hóa (thuộc 8 chiều: survival, power, order, reason, strategy, system, holistic, integral). Chỉ chọn 2-3 thuộc tính để thay đổi (giá trị từ 0.0 đến 1.0).
EOT;

        try {
            $content = $this->aiGateway->feature('decision')->chat([
                ['role' => 'system', 'content' => $systemPersona],
                ['role' => 'user',   'content' => $prompt]
            ], [
                'temperature' => 0.7,
                'timeout' => 60
            ]);
        } catch (\Throwable $e) {
            Log::warning('MacroAgentDecisionService: AI call failed', ['error' => $e->getMessage()]);
            return null;
        }

        if ($content) {
            // Clean markdown JSON block if present
            $content = preg_replace('/```json\s*/', '', $content);
            $content = preg_replace('/```\s*/', '', $content);
            return json_decode($content, true);
        }

        return $this->mockEdict($factionType, $traits, $entropy);
    }


    private function mockEdict(string $type, string $traits, float $entropy): array
    {
        if ($entropy > 0.7) {
            return [
                "edict_name" => "Thiết Quân Luật",
                "narrative_reason" => "Thế giới đang mục nát, chỉ có sức mạnh quân sự mới thanh tẩy được cặn bã.",
                "policy_focus" => "WAR",
                "drift_target" => ["power" => 0.9, "survival" => 0.8]
            ];
        }
        
        if ($type === 'ruler') {
             return [
                "edict_name" => "Chiếu Cầu Hiền",
                "narrative_reason" => "Trị quốc cần người tài, ta mở mang tri thức để duy trì thái bình.",
                "policy_focus" => "KNOWLEDGE",
                "drift_target" => ["reason" => 0.8, "system" => 0.6]
            ];
        }

        return [
            "edict_name" => "Khẩn Hoang Lệnh",
            "narrative_reason" => "Tích trữ lương thảo là nền tảng của sự thịnh vượng lâu dài.",
            "policy_focus" => "AGRICULTURE",
            "drift_target" => ["order" => 0.7, "survival" => 0.6]
        ];
    }
}

