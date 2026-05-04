<?php

namespace App\Modules\Psychology\Services;

use App\Modules\Psychology\ValueObjects\Impulse;
use App\Modules\Psychology\ValueObjects\Meaning;
use App\Modules\Psychology\ValueObjects\TraitVector;

/**
 * ImpulseGenerator – translates a Meaning into internal Impulses.
 *
 * One Meaning can generate MULTIPLE competing impulses.
 * This is where the internal conflict begins:
 * - A threatening event: desire to resist + fear to avoid
 * - A social opportunity: desire to approach + fear of rejection
 *
 * Big Five traits modulate impulse intensity (not which impulses appear).
 */
final class ImpulseGenerator
{
    /**
     * Generate impulses from a Meaning and actor's trait profile.
     *
     * @return Impulse[]
     */
    public function generate(Meaning $meaning, TraitVector $traits): array
    {
        $impulses = [];

        $threat    = $meaning->threatLevel();
        $valence   = $meaning->valence;
        $intensity = $meaning->intensity;
        $selfImpact = $meaning->selfImpact();

        // ── Desire (approach positive situations) ──
        if ($valence > 0.2) {
            $impulses[] = new Impulse(
                type:      Impulse::TYPE_DESIRE,
                action:    Impulse::ACTION_APPROACH,
                intensity: $valence * (0.5 + $traits->extraversion * 0.5),
                urgency:   $intensity * 0.6,
                tags:      ['social'],
            );
        }

        // ── Fear (avoidance of threat) ──
        if ($threat > 0.3) {
            $impulses[] = new Impulse(
                type:      Impulse::TYPE_FEAR,
                action:    Impulse::ACTION_AVOID,
                intensity: $threat * (0.6 + $traits->neuroticism * 0.4),
                urgency:   $threat * 0.8,
                tags:      ['protective'],
            );
        }

        // ── Anger / resistance ──
        if ($valence < -0.3 && $intensity > 0.4) {
            // Extravert + low agreeableness → more anger impulse
            $angerScalar = (1 - $traits->agreeableness) * 0.4 + $traits->extraversion * 0.3;
            $impulses[] = new Impulse(
                type:      Impulse::TYPE_DESIRE,
                action:    Impulse::ACTION_ATTACK,
                intensity: abs($valence) * $angerScalar,
                urgency:   $intensity * 0.5,
                tags:      ['aggressive'],
            );
        }

        // ── Withdrawal (sadness / helplessness) ──
        if ($valence < -0.5 && $threat < 0.4) {
            $impulses[] = new Impulse(
                type:      Impulse::TYPE_FEAR,
                action:    Impulse::ACTION_WITHDRAW,
                intensity: abs($valence) * 0.6,
                urgency:   $intensity * 0.4,
                tags:      ['passive'],
            );
        }

        // ── Identity defense (ego threat) ──
        if ($selfImpact < -0.25) {
            $impulses[] = new Impulse(
                type:      Impulse::TYPE_IDENTITY,
                action:    Impulse::ACTION_DEFEND,
                intensity: abs($selfImpact) * (0.5 + $traits->conscientiousness * 0.3),
                urgency:   abs($selfImpact) * 0.7,
                tags:      ['identity', 'ego'],
            );
        }

        // ── Duty / cooperation (conscientious & agreeable) ──
        if (in_array('social', $meaning->tags, true) && $traits->agreeableness > 0.5) {
            $impulses[] = new Impulse(
                type:      Impulse::TYPE_DUTY,
                action:    Impulse::ACTION_COOPERATE,
                intensity: $traits->agreeableness * $traits->conscientiousness * 0.6,
                urgency:   0.3,
                tags:      ['prosocial'],
            );
        }

        // Ensure at least one impulse (passive fallback)
        if (empty($impulses)) {
            $impulses[] = new Impulse(
                type:      Impulse::TYPE_DUTY,
                action:    Impulse::ACTION_WITHDRAW,
                intensity: 0.2,
                urgency:   0.1,
            );
        }

        return $impulses;
    }
}
