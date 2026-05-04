<?php

namespace App\Modules\Narrative\Services;

use Illuminate\Support\Facades\Log;

/**
 * TraitMapper: Maps 18D Actor Traits to Narrative Descriptions, Fate Tags, and Monologues.
 */
class TraitMapper
{
    public function generateMonologueSeed(array $traits, string $archetype): string
    {
        if (empty($traits)) return "Trình trạng trống rỗng. Mọi thứ mờ ảo.";
        $dominantIndex = $this->getDominantTraitIndex($traits);
        
        $seeds = [
            0 => "Quyền lực là con đường duy nhất.",
            1 => "Tôi phải vươn lên cao hơn nữa.",
            2 => "Kẻ yếu phải tuân lệnh.",
            3 => "Lòng trung thành là danh dự.",
            4 => "Tôi cảm nhận được nỗi đau của họ.",
            5 => "Chúng ta mạnh mẽ hơn khi đứng cùng nhau.",
            6 => "Tốt hơn là nên làm theo số đông.",
            7 => "Mọi thứ đều có cái giá của nó.",
            8 => "Bí ẩn này dẫn đến đâu?",
            9 => "Chân lý đã được định sẵn.",
            10 => "Rủi ro là một phần của cuộc chơi.",
            11 => "Bóng tối đang theo đuổi tôi.",
            12 => "Món nợ này phải được trả bằng máu.",
            13 => "Ngày mai sẽ tươi sáng hơn.",
            14 => "Nỗi buồn này không bao giờ dứt.",
            15 => "Tôi là đỉnh cao của sự tồn tại.",
            16 => "Tôi không xứng đáng với điều này.",
            17 => "Thời gian đang cạn dần.",
        ];

        $thought = $seeds[$dominantIndex] ?? "Định mệnh đang gọi tên...";
        return "[$archetype] \"$thought\"";
    }

    public function getIntentTag(array $traits): string
    {
        if (empty($traits)) return 'UNKNOWN_INTENT';
        $dominantIndex = $this->getDominantTraitIndex($traits);
        
        $intentMap = [
            0 => 'DOMINANCE_DRIVEN', 1 => 'AMBITION_DRIVEN', 2 => 'COERCION_DRIVEN', 3 => 'LOYALTY_DRIVEN',
            4 => 'EMPATHY_DRIVEN', 5 => 'SOLIDARITY_DRIVEN', 6 => 'CONFORMITY_DRIVEN', 7 => 'PRAGMATISM_DRIVEN',
            8 => 'CURIOSITY_DRIVEN', 9 => 'DOGMATISM_DRIVEN', 10 => 'RISK_TOLERANCE_DRIVEN', 11 => 'FEAR_DRIVEN',
            12 => 'VENGEANCE_DRIVEN', 13 => 'HOPE_DRIVEN', 14 => 'GRIEF_DRIVEN', 15 => 'PRIDE_DRIVEN',
            16 => 'SHAME_DRIVEN', 17 => 'LONGEVITY_DRIVEN',
        ];

        return $intentMap[$dominantIndex] ?? 'UNKNOWN_INTENT';
    }

    public function mapToDescription(array $traits): string
    {
        $descriptions = [];
        if (($traits[0] ?? 0) > 0.7) $descriptions[] = "khát khao thống trị";
        if (($traits[1] ?? 0) > 0.7) $descriptions[] = "tham vọng lớn lao";
        if (($traits[2] ?? 0) > 0.7) $descriptions[] = "thích dùng quyền lực";
        if (($traits[4] ?? 0) > 0.7) $descriptions[] = "trắc ẩn";
        if (($traits[5] ?? 0) > 0.7) $descriptions[] = "hướng về cộng đồng";
        if (($traits[11] ?? 0) > 0.7) $descriptions[] = "luôn sợ hãi";
        if (($traits[12] ?? 0) > 0.7) $descriptions[] = "đầy lòng thù hận";
        if (($traits[13] ?? 0) > 0.7) $descriptions[] = "tràn đầy hy vọng";

        if (empty($descriptions)) return "Một linh hồn bình thường trong dòng chảy vũ trụ.";
        return "Một kẻ " . implode(", ", $descriptions) . ".";
    }

    public function getFateTags(array $traits): array
    {
        $tags = [];
        $dominance = $traits[0] ?? 0;
        $ambition = $traits[1] ?? 0;
        $empathy = $traits[4] ?? 0;
        $curiosity = $traits[8] ?? 0;
        $dogmatism = $traits[9] ?? 0;
        $vengeance = $traits[12] ?? 0;
        $hope = $traits[13] ?? 0;
        $pragmatism = $traits[7] ?? 0;

        if ($dominance > 0.8 && $ambition > 0.8) $tags[] = "The Conqueror";
        if ($empathy > 0.8 && $hope > 0.8) $tags[] = "The Messiah";
        if ($curiosity > 0.9) $tags[] = "The Void-Seeker";
        if ($vengeance > 0.85) $tags[] = "The Avenger";
        if ($dogmatism > 0.85) $tags[] = "The Inquisitor";
        
        if ($pragmatism > 0.8 && $curiosity > 0.7 && $dogmatism < 0.3) {
            $tags[] = "Awareness_of_the_Clock";
            $tags[] = "Simulation_Skepticism";
        }

        return $tags;
    }

    protected function getDominantTraitIndex(array $traits): int
    {
        $maxVal = -1.0; $maxIdx = 0;
        foreach ($traits as $idx => $val) {
            if ($val > $maxVal) { $maxVal = $val; $maxIdx = $idx; }
        }
        return $maxIdx;
    }
}
