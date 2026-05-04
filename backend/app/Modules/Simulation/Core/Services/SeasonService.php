<?php

namespace App\Modules\Simulation\Core\Services;

use App\Modules\Geography\ValueObjects\Weather;

/**
 * Hệ thống Mùa (Season). Quy định vòng lặp thời tiết theo chu kỳ.
 * Chia năm thành 4 mùa, mỗi mùa kéo dài N ticks.
 */
class SeasonService
{
    public const SEASON_SPRING = 'spring';
    public const SEASON_SUMMER = 'summer';
    public const SEASON_AUTUMN = 'autumn';
    public const SEASON_WINTER = 'winter';

    private const TICKS_PER_SEASON = 50; // 50 tick = 1 mùa. 200 ticks = 1 năm.

    /**
     * Xác định mùa hiện tại dựa vào tick number.
     */
    public function getCurrentSeason(int $tick): string
    {
        $yearProgress = $tick % (self::TICKS_PER_SEASON * 4);

        if ($yearProgress < self::TICKS_PER_SEASON) return self::SEASON_SPRING;
        if ($yearProgress < self::TICKS_PER_SEASON * 2) return self::SEASON_SUMMER;
        if ($yearProgress < self::TICKS_PER_SEASON * 3) return self::SEASON_AUTUMN;
        return self::SEASON_WINTER;
    }

    /**
     * Tạo Weather phù hợp với mùa hiện tại.
     * Mùa xuân → Mưa (Boost growth).
     * Mùa đông → Tuyết (Stop growth, damage shelter, kill food).
     */
    public function getSeasonalWeather(string $season): Weather
    {
        return match($season) {
            self::SEASON_SPRING => new Weather(Weather::TYPE_RAIN, rand(5, 15), 0.6),
            self::SEASON_SUMMER => new Weather(Weather::TYPE_CLEAR, rand(10, 20), 0.3),
            self::SEASON_AUTUMN => new Weather(Weather::TYPE_CLEAR, rand(5, 10), 0.5),
            self::SEASON_WINTER => new Weather(Weather::TYPE_SNOW, rand(10, 20), 0.8),
            default => new Weather(Weather::TYPE_CLEAR, 10, 0.5),
        };
    }

    /**
     * Hệ số sinh trưởng phụ thuộc mùa (áp dụng cho tài nguyên).
     */
    public function getSeasonalGrowthMultiplier(string $season): float
    {
        return match($season) {
            self::SEASON_SPRING => 2.0,   // Xuân: Cây cối nở rộ
            self::SEASON_SUMMER => 1.5,   // Hạ: Tốt nhưng nóng
            self::SEASON_AUTUMN => 0.8,   // Thu: Thu hoạch cuối
            self::SEASON_WINTER => 0.0,   // Đông: Cây chết hết
            default => 1.0,
        };
    }
}
