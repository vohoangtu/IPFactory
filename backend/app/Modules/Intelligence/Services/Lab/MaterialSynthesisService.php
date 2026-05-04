<?php

namespace App\Modules\Intelligence\Services\Lab;

use App\Modules\World\Models\Universe;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MaterialSynthesisService
{
    public function __construct(
        protected \App\Modules\Intelligence\Services\AI\AiGateway $aiGateway
    ) {}

    /**
     * Synthesize a new material based on universe scars and core signatures.
     */
    public function synthesize(Universe $universe, array $activeMaterials, array $mythScars): ?array
    {
        $prompt = $this->buildPrompt($universe, $activeMaterials, $mythScars);
        $json = $this->callLlm($prompt);

        if (!$json) {
            return null;
        }

        return $this->parseResponse($json);
    }

    protected function buildPrompt(Universe $universe, array $activeMaterials, array $mythScars): string
    {
        $matStr = implode(', ', $activeMaterials);
        $scarStr = implode(', ', $mythScars);

        return <<<EOT
Bạn là một AI phân tích tiến hóa trong hệ thống WorldOS.
Nhiệm vụ: Sáng tạo MỘT Khái niệm vật chất/ý niệm (Material) hoàn toàn mới dựa trên bối cảnh hiện tại.
Bối cảnh Vũ trụ #{$universe->id}:
- Các vật chất/ý niệm đang tồn tại: {$matStr}
- Vết sẹo lịch sử (Trauma/Myth Scars): {$scarStr}

Hãy kết hợp các yếu tố trên để tạo ra một vật chất/ý niệm mang tính đột phá (ví dụ: công nghệ mới, tôn giáo mới, chế độ xã hội mới).

TRẢ VỀ DUY NHẤT 1 KHỐI JSON (KHÔNG KÈM TEXT GIẢI THÍCH) THEO FORMAT:
{
  "name": "Tên Material (ví dụ: Động cơ Linh hồn)",
  "description": "Mô tả cơ chế hoạt động, nguồn gốc từ các vết sẹo lịch sử.",
  "parent_material_name": "Tên của một Material trong danh sách 'đang tồn tại' mà material mới này được phát triển/đột biến từ đó (ví dụ: Luyện kim).",
  "ontology": "Physical | Institutional | Symbolic | Behavioral",
  "pressure_coefficients": {
    "entropy": 0.2,
    "order": -0.1,
    "innovation": 0.4,
    "growth": 0.1,
    "trauma": 0.0
  }
}

Lưu ý:
- physics/pressure_coefficients: Giá trị từ -1.0 đến 1.0. Tối thiểu phải có các key trên.
- innovation thường dương nếu đây là một sự đột phá lớn.
EOT;
    }

    protected function callLlm(string $prompt): ?string
    {
        try {
            return $this->aiGateway->feature('lab')->chat([
                ['role' => 'system', 'content' => "Bạn là một AI khoa học của WorldOS, chuyên thiết kế JSON."],
                ['role' => 'user',   'content' => $prompt],
            ], [
                'temperature' => 0.8,
                'timeout' => 120,
            ]);
        } catch (\Throwable $e) {
            Log::warning('MaterialSynthesisService: LLM call failed', ['error' => $e->getMessage()]);
            return null;
        }
    }


    protected function parseResponse(string $responseText): ?array
    {
        // Try decoding directly
        $decoded = json_decode($responseText, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($decoded['name'])) {
            return $decoded;
        }

        // Try extracting json blocks if it's wrapped in markdown
        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $responseText, $matches)) {
            $decoded = json_decode(trim($matches[1]), true);
            if (json_last_error() === JSON_ERROR_NONE && isset($decoded['name'])) {
                return $decoded;
            }
        }

        // Try to find any curly brace structure
        if (preg_match('/(\{.*\})/s', $responseText, $matches)) {
            $decoded = json_decode(trim($matches[1]), true);
            if (json_last_error() === JSON_ERROR_NONE && isset($decoded['name'])) {
                return $decoded;
            }
        }

        Log::warning("Could not parse Material Synthesis JSON", ['response' => $responseText]);
        return null;
    }
}

