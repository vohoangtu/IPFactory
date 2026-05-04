<?php

namespace App\Modules\Institutions\Actions;

use App\Contracts\ActionInterface;
use App\Modules\World\Models\Universe;
use App\Modules\Institutions\Contracts\InstitutionalRepositoryInterface;
use App\Modules\Institutions\Entities\InstitutionalEntity;

class SpawnInstitutionAction implements ActionInterface
{
    public function __construct(
        private InstitutionalRepositoryInterface $institutionalRepository
    ) {}

    public function execute(mixed ...$args): mixed
    {
        return $this->doExecute($args[0], $args[1], $args[2], $args[3], $args[4] ?? 'genesis');
    }

    public function doExecute(Universe $universe, int $zoneId, int $tick, string $type, string $era = 'genesis'): InstitutionalEntity
    {
        $name = $this->generateName($type, $era);

        $entity = new InstitutionalEntity(
            id: null,
            universeId: $universe->id,
            name: $name,
            entityType: $type,
            ideologyVector: $this->randomIdeology($era),
            influenceMap: ["$zoneId" => 0.25],
            orgCapacity: 15.0,
            institutionalMemory: 0.5,
            legitimacy: 0.5,
            spawnedAtTick: $tick
        );

        $this->institutionalRepository->save($entity);

        return $entity;
    }

    private function generateName(string $type, string $era): string
    {
        $eraPrefixes = [
            'paleolithic' => [
                'cult' => ['Lửa thiêng', 'Bóng đêm', 'Sấm sét', 'Đất mẹ', 'Huyền bí'],
                'order' => ['Hội đồng', 'Gia tộc', 'Liên minh', 'Lớp trẻ', 'Săn bắn'],
                'rebel' => ['Phản kháng', 'Tự do', 'Dời hang', 'Bẻ cung', 'Lửa mới'],
                'civilization' => ['Lạc', 'Việt', 'Bách Bình', 'Âu', 'Văn Lang']
            ],
            'medieval' => [
                'cult' => ['U minh', 'Huyền ảo', 'Thiên đạo', 'Tà phái', 'U cung'],
                'order' => ['Hoàng gia', 'Thánh khiết', 'Trưởng lão', 'Chính nghĩa', 'Hàn lâm'],
                'rebel' => ['Khởi nghĩa', 'Phá xiềng', 'Rạng đông', 'Áo vải', 'Cần vương'],
                'civilization' => ['Đại', 'Thần', 'Long', 'Phượng', 'Chính']
            ],
            'cyberpunk' => [
                'cult' => ['Data-Zen', 'Silicon', 'Mã nguồn', 'Vô ảnh', 'Hư vô'],
                'order' => ['Tập đoàn', 'Hệ thống', 'Liên hợp', 'Công nghệ', 'An ninh'],
                'rebel' => ['Glitch', 'Virus', 'Mạng lưới', 'Underground', 'Neon'],
                'civilization' => ['Metropolis', 'Neo', 'Hyper', 'Titan', 'Apex']
            ]
        ];
        
        $eraSuffixes = [
            'paleolithic' => [
                'cult' => ['Đàn', 'Mã', 'Huyệt', 'Miếu', 'Đền'],
                'order' => ['Bộ lạc', 'Bầy', 'Đội', 'Đoàn', 'Nhóm'],
                'rebel' => ['Phe', 'Cánh', 'Lớp', 'Toán', 'Đội'],
                'civilization' => ['Tộc', 'Đàn', 'Sóc', 'Bản', 'Mường']
            ],
            'medieval' => [
                'cult' => ['Giáo', 'Hội', 'Tông', 'Cung', 'Tự'],
                'order' => ['Viện', 'Môn', 'Các', 'Phủ', 'Sảnh'],
                'rebel' => ['Quân', 'Đoàn', 'Mạng', 'Hội', 'Đảng'],
                'civilization' => ['Quốc', 'Bang', 'Triều', 'Đế Chế', 'Làng']
            ],
            'cyberpunk' => [
                'cult' => ['Node', 'Layer', 'Sect', 'Cloud', 'Core'],
                'order' => ['Corp', 'System', 'Syndicate', 'Agency', 'Foundation'],
                'rebel' => ['Cell', 'Node', 'Net', 'Faction', 'Unit'],
                'civilization' => ['City', 'Arcology', 'District', 'Hub', 'Sphere']
            ]
        ];
        
        $eraKey = strtolower($era);
        if (!isset($eraPrefixes[$eraKey])) $eraKey = 'medieval'; // Fallback

        $typeKey = $type === 'CIVILIZATION' ? 'civilization' : $type;
        $prefix = $eraPrefixes[$eraKey][$typeKey][array_rand($eraPrefixes[$eraKey][$typeKey])] ?? 'Vô danh';
        $suffix = $eraSuffixes[$eraKey][$typeKey][array_rand($eraSuffixes[$eraKey][$typeKey])] ?? 'Thể';
        
        return $prefix . ' ' . $suffix . ' - ' . mt_rand(100, 999);
    }

    private function randomIdeology(string $era): array
    {
        $era = strtolower($era);
        $base = [
            'tradition' => (mt_rand(0, 100) / 100.0),
            'innovation' => (mt_rand(0, 100) / 100.0),
            'trust' => (mt_rand(0, 100) / 100.0),
            'violence' => (mt_rand(0, 100) / 100.0),
            'respect' => (mt_rand(0, 100) / 100.0),
            'myth' => (mt_rand(0, 100) / 100.0),
        ];

        if ($era === 'paleolithic') {
            $base['myth'] = max(0.6, $base['myth']);
            $base['tradition'] = max(0.7, $base['tradition']);
            $base['innovation'] = min(0.3, $base['innovation']);
        } elseif ($era === 'cyberpunk') {
            $base['innovation'] = max(0.7, $base['innovation']);
            $base['myth'] = min(0.4, $base['myth']);
            $base['trust'] = min(0.5, $base['trust']);
        }

        return $base;
    }
}

