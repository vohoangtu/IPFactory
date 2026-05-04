<?php

namespace Tests\Unit\Psychology;

use App\Modules\Psychology\Services\StateEvolutionService;
use App\Modules\Psychology\ValueObjects\Meaning;
use App\Modules\Psychology\ValueObjects\MemoryItem;
use App\Modules\Psychology\ValueObjects\MemoryStream;
use App\Modules\Psychology\ValueObjects\PsychologicalState;
use Tests\TestCase;

class StateEvolutionTest extends TestCase
{
    private StateEvolutionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StateEvolutionService();
    }

    public function test_emotion_decays_toward_baseline_over_ticks(): void
    {
        $state  = new PsychologicalState(fear: 0.9, anger: 0.8, sadness: 0.7);
        $memory = MemoryStream::empty();

        // Neutral meaning (no new stimulus)
        $neutralMeaning = Meaning::neutral();

        // Run 20 ticks with neutral meaning
        for ($i = 0; $i < 20; $i++) {
            $this->service->evolve($state, $neutralMeaning, $memory, 0.0, $i);
        }

        $this->assertLessThan(0.5, $state->fear,    'Fear should decay over 20 ticks');
        $this->assertLessThan(0.5, $state->anger,   'Anger should decay over 20 ticks');
        $this->assertLessThan(0.5, $state->sadness, 'Sadness should decay over 20 ticks');
    }

    public function test_high_threat_meaning_increases_fear_and_anger(): void
    {
        $state  = PsychologicalState::baseline();
        $memory = MemoryStream::empty();

        $threat = new Meaning(
            type:      Meaning::TYPE_THREAT,
            valence:   -0.8,
            intensity: 0.8,
            certainty: 0.9,
        );

        $this->service->evolve($state, $threat, $memory, 0.0, 1);

        $this->assertGreaterThan(0, $state->fear,  'Threat event should increase fear');
        $this->assertGreaterThan(0, $state->anger, 'Threat event should increase anger');
    }

    public function test_high_intensity_threat_creates_trauma_memory(): void
    {
        $state  = PsychologicalState::baseline();
        $memory = MemoryStream::empty();

        $catastrophe = new Meaning(
            type:      Meaning::TYPE_THREAT,
            valence:   -0.95,
            intensity: 0.92,
            certainty: 0.99,
        );

        $this->service->evolve($state, $catastrophe, $memory, 0.0, 1);

        $traumaItems = $memory->filterByType(MemoryItem::TYPE_TRAUMA);
        $this->assertNotEmpty($traumaItems,
            'Very high intensity threat should create trauma memory');
    }

    public function test_positive_event_increases_joy_and_trust(): void
    {
        $state  = PsychologicalState::baseline();
        $memory = MemoryStream::empty();

        $positive = new Meaning(
            type:      Meaning::TYPE_SUPPORT,
            valence:   0.8,
            intensity: 0.6,
            certainty: 0.9,
        );

        $this->service->evolve($state, $positive, $memory, 0.0, 1);

        $this->assertGreaterThan(0, $state->joy, 'Positive event should increase joy');
    }

    public function test_values_stay_within_0_to_1_range(): void
    {
        $state  = new PsychologicalState(fear: 1.0, anger: 1.0, trust: 0.0);
        $memory = MemoryStream::empty();

        $extremeThreat = new Meaning(Meaning::TYPE_THREAT, -1.0, 1.0, 1.0);

        // Apply multiple extreme events
        for ($i = 0; $i < 5; $i++) {
            $this->service->evolve($state, $extremeThreat, $memory, 0.5, $i);
        }

        $this->assertGreaterThanOrEqual(0.0, $state->fear,    'Fear must be >= 0');
        $this->assertGreaterThanOrEqual(0.0, $state->anger,   'Anger must be >= 0');
        $this->assertGreaterThanOrEqual(0.0, $state->trust,   'Trust must be >= 0');
        $this->assertLessThanOrEqual(1.0, $state->fear,       'Fear must be <= 1');
        $this->assertLessThanOrEqual(1.0, $state->stress,     'Stress must be <= 1');
        $this->assertLessThanOrEqual(1.0, $state->trust,      'Trust must be <= 1');
    }

    public function test_zone_metrics_high_entropy_increases_fear_and_stress(): void
    {
        $state = PsychologicalState::baseline();

        $this->service->evolveFromZoneMetrics($state, [
            'entropy'    => 0.9,
            'fear'       => 0.8,
            'trauma'     => 0.5,
            'inequality' => 0.6,
        ]);

        $this->assertGreaterThan(0, $state->fear,   'High entropy should increase fear');
        $this->assertGreaterThan(0, $state->stress, 'High entropy should increase stress');
    }
}
