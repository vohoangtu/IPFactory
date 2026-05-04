<?php

namespace App\Modules\Simulation\Services\Civilization;

use App\Modules\Narrative\Models\Chronicle;
use App\Modules\World\Models\Universe;

class EraDetector
{
    /**
     * @param Universe $universe
     * @param array<string, mixed> $materialIdentity
     * @param array<string, mixed> $cultureIdentity
     * @return array<string, mixed>
     */
    public function detect(Universe $universe, array $materialIdentity, array $cultureIdentity): array
    {
        $material = mb_strtolower($materialIdentity['primary_material'] ?? 'unknown');
        $livelihood = mb_strtolower($materialIdentity['primary_livelihood'] ?? 'survival');
        $traits = (array) ($cultureIdentity['dominant_traits'] ?? []);
        
        $tick = (int) ($universe->current_tick ?? 0);
        
        // Phân tích mật độ biến cố gần đây để xác định tính chất kỷ nguyên
        $recentChronicles = Chronicle::query()
            ->where('universe_id', $universe->id)
            ->where('to_tick', '>', $tick - 500)
            ->get();
            
        $isCrisis = $recentChronicles->whereIn('type', ['crisis', 'collapse', 'war'])->count() > 2;
        $isGolden = $recentChronicles->where('importance', '>', 0.8)->count() > 1;

        $baseName = $this->determineBaseName($material, $livelihood, $isCrisis, $isGolden);
        $adjective = $this->determineAdjective($traits, $isCrisis, $isGolden);

        return [
            'name' => "Kỷ nguyên {$adjective} của {$baseName}",
            'descriptor' => $this->buildDescriptor($material, $livelihood, $traits, $isCrisis, $isGolden),
            'stage' => $isCrisis ? 'CRISIS' : ($isGolden ? 'FLOURISHING' : 'STABLE'),
            'focus_material' => $material,
        ];
    }

    protected function determineBaseName(string $material, string $livelihood, bool $isCrisis, bool $isGolden): string
    {
        $matMap = [
            'stone' => 'Đá Thạch',
            'iron' => 'Sắt Thép',
            'copper' => 'Đồng Đỏ',
            'bronze' => 'Đồng Thau',
            'obsidian' => 'Hắc Diệu Thạch',
            'gold' => 'Kim Loại Quý',
            'wood' => 'Thảo Mộc',
        ];

        $liveMap = [
            'fishing' => 'Đại Dương',
            'farming' => 'Đất Đai',
            'mining' => 'Lòng Đất',
            'crafting' => 'Kỹ Nghệ',
            'hunting' => 'Hoang Dã',
        ];

        $matName = $matMap[$material] ?? ucfirst($material);
        $liveName = $liveMap[$livelihood] ?? ucfirst($livelihood);

        if ($isCrisis) return "Sự Sụp Đổ {$matName}";
        if ($isGolden) return "Thời Đại Hoàng Kim {$liveName}";

        return "{$matName} và {$liveName}";
    }

    protected function determineAdjective(array $traits, bool $isCrisis, bool $isGolden): string
    {
        if ($isCrisis) return 'U Tối';
        if ($isGolden) return 'Rực Rỡ';

        $trait = mb_strtolower((string) ($traits[0] ?? 'bình lặng'));
        
        $traitMap = [
            'aggressive' => 'Chinh Phạt',
            'peaceful' => 'Thanh Bình',
            'diligent' => 'Cần Mẫn',
            'innovative' => 'Khai Phóng',
            'pious' => 'Thành Kính',
            'stoic' => 'Kiên Định',
        ];

        return $traitMap[$trait] ?? ucfirst($trait);
    }

    protected function buildDescriptor(string $material, string $livelihood, array $traits, bool $isCrisis, bool $isGolden): string
    {
        $traitsStr = implode(' và ', array_slice($traits, 0, 2));
        $desc = "Dân tộc đang trong thời kỳ sống dựa vào {$material} thông qua các hoạt động {$livelihood}. ";
        
        if ($isCrisis) {
            $desc .= "Sự gắn kết xã hội đang bị đe dọa bởi các biến động lớn.";
        } elseif ($isGolden) {
            $desc .= "Xã hội đang phát triển cực thịnh với bản sắc {$traitsStr} rõ nét.";
        } else {
            $desc .= "Đây là giai đoạn tích lũy và ổn định của bản sắc {$traitsStr}.";
        }

        return $desc;
    }
}
