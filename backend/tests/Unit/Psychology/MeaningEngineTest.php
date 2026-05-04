<?php

namespace Tests\Unit\Psychology;

use App\Modules\Psychology\Dsl\BehaviorDslLoader;
use App\Modules\Psychology\Services\MeaningEngine;
use App\Modules\Psychology\ValueObjects\MemoryItem;
use App\Modules\Psychology\ValueObjects\MemoryStream;
use App\Modules\Psychology\ValueObjects\PsychologicalState;
use App\Modules\Psychology\ValueObjects\TraitVector;
use Tests\TestCase;

/**
 * MeaningEngineTest
 *
 * Verifies: same event → different Meaning based on traits/memory.
 * This is the core emergent principle of the Psychology Layer.
 */
class MeaningEngineTest extends TestCase
{
    private MeaningEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $dslLoader    = new BehaviorDslLoader(base_path('resources/worldos_psychology/behaviors.json'));
        $this->engine = new MeaningEngine($dslLoader);
    }

    public function test_same_threat_event_is_more_negative_for_neurotic_actor(): void
    {
        $state = PsychologicalState::baseline();

        $highNeurotic = TraitVector::fromArray(['neuroticism' => 0.9]);
        $lowNeurotic  = TraitVector::fromArray(['neuroticism' => 0.1]);

        $highMemory = MemoryStream::empty();
        $lowMemory  = MemoryStream::empty();

        $meaningHigh = $this->engine->interpret('threat_encountered', $highNeurotic, $state, $highMemory);
        $meaningLow  = $this->engine->interpret('threat_encountered', $lowNeurotic,  $state, $lowMemory);

        // High neuroticism → more negative valence
        $this->assertLessThan($meaningLow->valence, $meaningHigh->valence,
            'High neuroticism actor should interpret threat more negatively');
    }

    public function test_trauma_memory_amplifies_negative_meaning(): void
    {
        $traits = TraitVector::neutral();
        $state  = PsychologicalState::baseline();

        $traumaMemory = MemoryStream::empty();
        $traumaMemory->push(MemoryItem::fromEvent(MemoryItem::TYPE_TRAUMA, -0.9, 0.9, 1));
        $traumaMemory->push(MemoryItem::fromEvent(MemoryItem::TYPE_TRAUMA, -0.8, 0.85, 2));

        $emptyMemory = MemoryStream::empty();

        $withTrauma    = $this->engine->interpret('social_conflict', $traits, $state, $traumaMemory);
        $withoutTrauma = $this->engine->interpret('social_conflict', $traits, $state, $emptyMemory);

        // Trauma memory → more negative valence
        $this->assertLessThan(
            $withoutTrauma->valence,
            $withTrauma->valence,
            'Trauma memory should make the same event feel more negative'
        );
    }

    public function test_high_trust_relation_reduces_threat_perception(): void
    {
        $traits = TraitVector::neutral();
        $state  = PsychologicalState::baseline();
        $memory = MemoryStream::empty();

        $highTrust = $this->engine->interpret(
            'social_conflict', $traits, $state, $memory,
            ['liking' => 0.9, 'fear_of_source' => 0.0]
        );
        $lowTrust = $this->engine->interpret(
            'social_conflict', $traits, $state, $memory,
            ['liking' => 0.0, 'fear_of_source' => 0.5]
        );

        // High liking → less negative, low trust + fear → more negative
        $this->assertGreaterThan($lowTrust->valence, $highTrust->valence,
            'High trust/liking should reduce threat impact');
    }

    public function test_two_different_actors_produce_different_meanings_for_same_event(): void
    {
        $state  = PsychologicalState::baseline();
        $memory = MemoryStream::empty();

        $warrior = TraitVector::fromArray([
            'neuroticism' => 0.1, 'agreeableness' => 0.1, 'extraversion' => 0.8
        ]);
        $anxious = TraitVector::fromArray([
            'neuroticism' => 0.9, 'agreeableness' => 0.7, 'extraversion' => 0.2
        ]);

        $warriorMeaning = $this->engine->interpret('threat_encountered', $warrior, $state, $memory);
        $anxiousMeaning = $this->engine->interpret('threat_encountered', $anxious, $state, $memory);

        $this->assertNotEquals(
            $warriorMeaning->valence,
            $anxiousMeaning->valence,
            'Different trait profiles should produce different meanings for the same event'
        );
    }

    public function test_zone_metrics_high_entropy_produces_threat_meaning(): void
    {
        $meaning = $this->engine->interpretFromZoneMetrics([
            'entropy' => 0.9, 'fear' => 0.7, 'trauma' => 0.3
        ]);

        $this->assertLessThan(0, $meaning->valence, 'High entropy/fear zone should produce negative meaning');
        $this->assertGreaterThan(0, $meaning->intensity, 'High entropy zone should have significant intensity');
    }
}
