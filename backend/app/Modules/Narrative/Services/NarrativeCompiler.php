<?php

namespace App\Modules\Narrative\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Narrative Compiler: Translates distorted data vectors into mythic text.
 * Represents the "Blind Historian" role.
 * Uses LLM first; falls back to template-based generation.
 */
class NarrativeCompiler
{
    public function __construct(
        protected \App\Modules\Intelligence\Services\AI\AiGateway $aiGateway
    ) {}

    public function setUniverse(\App\Modules\World\Models\Universe $universe): self
    {
        $this->universe = $universe;
        return $this;
    }
    /**
     * Compile a narrative. Tries LLM first; falls back to template.
     */
    public function compile(array $distortedData, float $noise): string
    {
        // Try LLM-powered narrative first
        $llmResult = $this->compileLlm($distortedData, $noise);
        if ($llmResult !== null) {
            return $llmResult;
        }

        // Fallback to template-based
        return $this->compileTemplate($distortedData, $noise);
    }

    /**
     * LLM-powered narrative generation.
     * When distortedData contains 'historical_block' (Narrative v2), the prompt is fact-first.
     */
    protected function compileLlm(array $distortedData, float $noise): ?string
    {
        $entropy    = $distortedData['entropy']          ?? 0.5;
        $stability  = $distortedData['stability_index'] ?? 0.5;
        $clarityTag = match (true) {
            $noise < 0.2 => 'Chân Thực',
            $noise < 0.5 => 'Mơ Hồ',
            $noise < 0.8 => 'Huyền Sử',
            default      => 'Hư Vô',
        };

        $factBlock = '';
        if (! empty($distortedData['historical_block']) && is_array($distortedData['historical_block'])) {
            $hb = $distortedData['historical_block'];
            $factBlock = "\nDữ liệu lịch sử (fact, bắt buộc dựa vào): Year " . ($hb['year'] ?? $hb['tick'] ?? '?')
                . ", Tick " . ($hb['tick'] ?? '?')
                . ". Metrics: " . json_encode($hb['metrics'] ?? [])
                . ". Events: " . implode(', ', $hb['events'] ?? [])
                . ".\n";
        }

        $prompt = "Bạn là Nhà sử gia Mù quảng - The Blind Historian - của WorldOS.\n"
            . ($factBlock ?: "Biết: Entropy={$entropy}, Độ ổn định={$stability}, Độ rõ nét={$clarityTag}.\n")
            . "Hãy viết một đoạn biên niên sử chi tiết, giàu hình ảnh và mang tính sử thi phù hợp với mức \'$clarityTag\'.\n"
            . "Tập trung vào sự biến đổi của thế giới và cảm xúc của vạn vật. Ngôn ngữ: Tiếng Việt. Không giải thích thêm.";

        return $this->aiGateway->feature('narrative')->chat([
            ['role' => 'system', 'content' => 'You are the Blind Historian of WorldOS.'],
            ['role' => 'user',   'content' => $prompt],
        ], [
            'temperature' => min(0.9, $noise + 0.3),
            'timeout' => 120
        ]);

    }


    /**
     * Template-based fallback narrative generation.
     */
    protected function compileTemplate(array $distortedData, float $noise): string
    {

        $entropy = $distortedData['entropy'] ?? 0.5;
        $stability = $distortedData['stability_index'] ?? 0.5;
        $metaphysics = $distortedData['metrics']['ethos'] ?? [];
        
        $spirituality = $metaphysics['spirituality'] ?? 0.5;
        $openness = $metaphysics['openness'] ?? 0.5;

        $templates = $this->getTemplates($noise);
        $narrative = "";

        // Introduction based on general state
        if ($entropy > 0.8) {
            $narrative .= $this->pick($templates['entropy_high']) . " ";
        } elseif ($stability < 0.3) {
            $narrative .= $this->pick($templates['stability_low']) . " ";
        } else {
            $narrative .= $this->pick($templates['order_stable']) . " ";
        }

        // Metaphysical influence
        if ($spirituality > 0.7) {
            $narrative .= $this->pick($templates['spirit_high']) . " ";
        } elseif ($openness > 0.7) {
            $narrative .= $this->pick($templates['tech_high']) . " ";
        }

        // Add "Epistemic awareness" if noise is high
        if ($noise > 0.6) {
            $narrative .= "\n\n[" . $this->pick($templates['noise_meta']) . "]";
        }

        return $narrative;
    }

    protected function pick(array $items): string
    {
        $prng = $this->universe 
            ? \App\Support\Simulation\SimulationPRNG::forUniverse($this->universe)
            : new \App\Support\Simulation\SimulationPRNG(rand(1, 1000));
            
        if (count($items) === 0) return '';
        return $prng->randomElement($items);
    }

    protected function getTemplates(float $noise): array
    {
        // Templates vary based on noise (higher noise = more abstract/mythic)
        if ($noise > 0.6) {
            return [
                'entropy_high' => [
                    "Hư vô đang gào thét qua những vết nứt của thực tại.",
                    "Hào quang của sự tồn tại đang mờ dần vào cõi hỗn mang.",
                    "Những mảnh vỡ của thời gian trôi dạt trong biển mênh mông của cái chết."
                ],
                'stability_low' => [
                    "Các định chế cổ xưa sụp đổ như những lâu đài cát trước thủy triều.",
                    "Sự hỗn loạn ngự trị, biến trật tự thành một ký ức xa xăm.",
                    "Tiếng vang của sự tan rã vang lên từ tận cùng của linh hồn."
                ],
                'order_stable' => [
                    "Một sự tĩnh lặng đáng sợ bao trùm lên vạn vật.",
                    "Quy luật vận hành trong sự im lặng của những vì sao.",
                    "Thế giới tạm thời ngủ yên trong vòng tay của định mệnh."
                ],
                'spirit_high' => [
                    "Linh thể thăng hoa qua những đám mây ảo ảnh.",
                    "Tiếng thì thầm của chư thần lấp đầy những khoảng không vô định.",
                    "Mọi ý thức kết nối với nhau bằng những sợi tơ huyền diệu."
                ],
                'tech_high' => [
                    "Logic lạnh lẽo đang dần thay thế những giấc mơ phàm trần.",
                    "Bánh xe của sự đổi mới nghiền nát những giáo điều cổ hủ.",
                    "Khuôn mẫu của sự sáng tạo đang bóp méo hình dạng của thiên nhiên."
                ],
                'noise_meta' => [
                    "Ghi chép này mập mờ như sương khói.",
                    "Sự thật bị che lấp bởi tấm màn của Hư Vô.",
                    "Ký ức này không thuộc về thực tại mà là một giấc mộng mị."
                ]
            ];
        }

        return [
            'entropy_high' => [
                "Chỉ số Entropy tăng vọt, đe dọa cấu trúc vật chất.",
                "Sự mất trật tự lan rộng khắp các vùng không gian.",
                "Hệ thống đang tiến gần tới điểm sụp đổ nhiệt động lực học."
            ],
            'stability_low' => [
                "Xung đột xã hội đang làm xói mòn các định chế.",
                "Tính ổn định của vũ trụ suy giảm nghiêm trọng.",
                "Cấu trúc quản trị đang rạn nứt trước áp lực biến động."
            ],
            'order_stable' => [
                "Vũ trụ đang ở trạng thái cân bằng tương đối.",
                "Các dòng chảy năng lượng vận hành ổn định.",
                "Trật tự hiện hữu đang được duy trì hiệu quả."
            ],
            'spirit_high' => [
                "Xu hướng tâm linh chiếm ưu thế trong nhận thức cộng đồng.",
                "Năng lượng tinh thần bộc phát mạnh mẽ.",
                "Sự thăng hoa ý thức đang định hình lại thực tại."
            ],
            'tech_high' => [
                "Tiến bộ kỹ thuật đang thúc đẩy những thay đổi cấu trúc.",
                "Khai phóng tư duy dẫn đến sự bùng nổ của tri thức.",
                "Công nghệ đang trở thành động lực chính của sự tiến hóa."
            ],
            'noise_meta' => [
                "Dữ liệu có dấu hiệu sai lệch nhẹ do nhiễu hệ thống.",
                "Cần kiểm chứng lại độ chính xác của các chỉ số.",
                "Các quan sát mang tính tương đối."
            ]
        ];
    }
}


