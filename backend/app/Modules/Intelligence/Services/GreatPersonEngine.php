<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\World\Models\Universe;
use App\Modules\Intelligence\Entities\ActorState;
use App\Modules\Simulation\Services\Ecology\SimulationPRNG;

/**
 * GreatPersonEngine (Điểm kết tinh Lịch sử)
 * 
 * Chịu trách nhiệm phát hiện và kích hoạt sự xuất hiện của các cá nhân kiệt xuất
 * dựa trên sự bão hòa của các trường lực (Field Saturation) và áp lực lịch sử.
 */
class GreatPersonEngine
{
    private SimulationPRNG $prng;

    public const TYPES = [
        'SCIENTIST' => 'Học Giả Vĩ Đại',
        'GENERAL'   => 'Đại Tướng Quân',
        'RULER'     => 'Minh Quân',
        'MERCHANT'  => 'Đại Phú Hộ',
        'PROPHET'   => 'Thánh Nhân',
        'ARTIST'    => 'Đại Nghệ Sĩ'
    ];

    public function __construct(SimulationPRNG $prng)
    {
        $this->prng = $prng;
    }

    /**
     * Đánh giá và cố gắng kích hoạt sự xuất hiện của Vĩ nhân cho một Actor.
     * 
     * @param ActorState $actor 
     * @param Universe $universe
     * @param array $zoneFields Các trường lực của vùng hiện tại
     * @param float $networkDensity Mật độ mạng lưới (Social Cohesion)
     * @return string|null Loại Vĩ nhân nếu được kích hoạt, ngược lại null
     */
    public function evaluateCrystallization(
        ActorState $actor, 
        Universe $universe, 
        array $zoneFields, 
        float $networkDensity
    ): ?string {
        $type = $this->determinePotentialType($actor);
        if (!$type) return null;

        $potential = $this->calculatePotential($actor, $universe, $zoneFields, $networkDensity, $type);
        
        // Probabilistic Spawn dựa trên Sigmoid (Cực hiếm)
        $probability = $this->sigmoid($potential - 4.5); // Threshold bão hòa cao hơn để giảm lạm phát vĩ nhân
        
        if ($this->prng->nextFloat() < $probability) {
            return $type;
        }

        return null;
    }

    /**
     * Xác định loại vĩ nhân tiềm năng dựa trên tố chất (Traits).
     */
    private function determinePotentialType(ActorState $actor): ?string
    {
        $traits = $actor->traits;

        if (($traits['Curiosity'] ?? 0) > 0.9) return 'SCIENTIST';
        if (($traits['Dominance'] ?? 0) > 0.9 && ($traits['Amibition'] ?? 0) > 0.8) return 'RULER';
        if (($traits['Dominance'] ?? 0) > 0.8 && ($traits['Resilience'] ?? 0) > 0.8) return 'GENERAL';
        if (($traits['Pragmatism'] ?? 0) > 0.9) return 'MERCHANT';
        if (($traits['Hope'] ?? 0) > 0.9) return 'PROPHET';
        if (($traits['Creativity'] ?? 0) > 0.9) return 'ARTIST'; // Giả định có trait Creativity

        return null;
    }

    /**
     * Tính toán tiềm năng kết tinh: talent^2 + field_density + historical_pressure + network_density.
     */
    private function calculatePotential(
        ActorState $actor, 
        Universe $universe, 
        array $zoneFields, 
        float $networkDensity,
        string $type
    ): float {
        $talent = $this->getTalentScore($actor, $type);
        
        $fieldKey = $this->mapTypeToField($type);
        $fieldDensity = $zoneFields[$fieldKey] ?? 0.5;
        
        // Áp lực lịch sử tỷ lệ thuận với Entropy (Khủng hoảng tạo vĩ nhân)
        $historicalPressure = $universe->entropy / 2.0; 

        return ($talent ** 2) + $fieldDensity + $historicalPressure + $networkDensity;
    }

    private function getTalentScore(ActorState $actor, string $type): float
    {
        return match($type) {
            'SCIENTIST' => $actor->traits['Curiosity'] ?? 0.5,
            'RULER'     => ($actor->traits['Dominance'] ?? 0.5) * 0.7 + ($actor->traits['Pride'] ?? 0.5) * 0.3,
            'GENERAL'   => ($actor->traits['Dominance'] ?? 0.5) * 0.6 + ($actor->traits['Resilience'] ?? 0.5) * 0.4,
            'MERCHANT'  => $actor->traits['Pragmatism'] ?? 0.5,
            'PROPHET'   => $actor->traits['Hope'] ?? 0.5,
            'ARTIST'    => $actor->traits['Curiosity'] ?? 0.5, // Fallback
            default     => 0.5
        };
    }

    private function mapTypeToField(string $type): string
    {
        return match($type) {
            'SCIENTIST' => 'knowledge',
            'RULER'     => 'power',
            'GENERAL'   => 'power',
            'MERCHANT'  => 'wealth',
            'PROPHET'   => 'meaning',
            'ARTIST'    => 'status',
            default     => 'survival'
        };
    }

    private function sigmoid(float $x): float
    {
        return 1 / (1 + exp(-$x));
    }
}


