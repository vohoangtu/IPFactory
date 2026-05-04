<?php

namespace App\Modules\Psychology\Services;

use App\Modules\Psychology\ValueObjects\CultureTension;
use App\Modules\Psychology\ValueObjects\PsychologicalState;
use App\Modules\Psychology\ValueObjects\TraitVector;

class CulturePropagationService
{
    /**
     * Lây nhiễm cảm xúc (Emotion Contagion) của đám đông lên một cá nhân cụ thể.
     * Cảm xúc đám đông cực đoan (fear > 0.8) sẽ bị lây sang Actor, bất chấp TraitVector của họ,
     * tuy nhiên những người có extraversion (hướng ngoại) hoặc openness (cởi mở) cao sẽ dễ bị "lây nhiễm" hơn.
     */
    public function applyContagion(
        PsychologicalState $actorState,
        TraitVector $actorTraits,
        float $zoneAverageFear,
        float $zoneAverageJoy,
        float $zoneAverageAnger
    ): PsychologicalState {
        // Mức độ dễ lây nhiễm = Openness + Extraversion (Tạo một trọng số từ 0.1 -> 1.0)
        // Traits chạy từ -1 đến 1. Neutral = 0.
        // normalized_extra = (extra + 1)/2 => [0, 1]
        $openness = ($actorTraits->openness + 1.0) / 2.0;
        $extraversion = ($actorTraits->extraversion + 1.0) / 2.0;

        // Base susceptibility là 0.2, cởi mở/hướng ngoại làm tăng rate.
        $susceptibility = 0.2 + ($openness * 0.4) + ($extraversion * 0.4);

        // Nếu Zone có cảm xúc cực trị (ví dụ panic), lây lan áp đảo cá nhân
        // Tính delta = (Zone - Actor) * susceptibility
        $fearDelta = ($zoneAverageFear - $actorState->fear) * $susceptibility;
        $joyDelta = ($zoneAverageJoy - $actorState->joy) * $susceptibility;
        $angerDelta = ($zoneAverageAnger - $actorState->anger) * $susceptibility;

        // Fear/Anger lây nhanh hơn Joy
        if ($fearDelta > 0) $fearDelta *= 1.5;
        if ($angerDelta > 0) $angerDelta *= 1.2;

        $actorState->applyDelta([
            'fear'    => $fearDelta,
            'joy'     => $joyDelta,
            'anger'   => $angerDelta,
            'sadness' => 0.0,
            'disgust' => 0.0
        ]);
        return $actorState;
    }

    /**
     * Gây áp lực tâm lý (Stress) lên Actor nếu hành vi/xu hướng của họ đi ngược với Văn Hóa chung.
     *
     * @param CultureTension $tension Trạng thái văn hóa hiện tại của Zone.
     * @param string $actorBehavior Hành động mà Actor dự định hoặc vừa làm (ví dụ: 'isolate').
     * @return float Lượng Stress Delta phạt lên Actor (Peer pressure).
     */
    public function calculatePeerPressureStress(CultureTension $tension, string $actorBehavior): float
    {
        $pressureIntensity = $tension->computePeerPressureIntensity();
        
        if ($pressureIntensity < 0.2) {
            return 0.0; // Văn hóa dễ dãi, đa nguyên, ít ràng buộc
        }

        $stressPenalty = 0.0;

        // Collectivism vs Individualism (-1: Collectivism, 1: Individualism)
        if ($tension->collectivismVsIndividualism < -0.3) {
            // Văn hóa đề cao tập thể. Trừng phạt kẻ tách biệt.
            if ($actorBehavior === 'isolate') {
                $stressPenalty += 0.1 * $pressureIntensity;
            }
        } elseif ($tension->collectivismVsIndividualism > 0.3) {
            // Văn hóa cá nhân. Trừng phạt sự can thiệp quá sâu/quỳ lụy.
            if ($actorBehavior === 'submit') {
                $stressPenalty += 0.05 * $pressureIntensity;
            }
        }

        // Peace vs Aggression (-1: Hòa bình, 1: Cuồng sát)
        if ($tension->peaceVsAggression < -0.5) {
            if ($actorBehavior === 'attack') {
                $stressPenalty += 0.2 * $pressureIntensity; // Bại hoại đạo đức
            }
        } elseif ($tension->peaceVsAggression > 0.5) {
            if ($actorBehavior === 'cooperate' || $actorBehavior === 'passive') {
                $stressPenalty += 0.1 * $pressureIntensity; // Kẻ yếu đuối bị khinh bỉ
            }
        }

        return min(1.0, $stressPenalty);
    }
}
