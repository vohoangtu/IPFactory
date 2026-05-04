<?php

namespace Tests\Unit\Psychology;

use App\Modules\Psychology\Services\MythGenerator;
use Tests\TestCase;

class MythGeneratorTest extends TestCase
{
    private MythGenerator $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MythGenerator();
    }

    public function test_generates_catastrophe_myth_during_high_fear(): void
    {
        $metrics = [
            'fear' => 0.9,
            'trauma' => 0.6,
            'entropy' => 0.85
        ];
        
        $myth = $this->service->evaluateFromZoneMetrics($metrics, ['dragon_attack'], 100);

        $this->assertNotNull($myth);
        $this->assertEquals('dragon_attack', $myth->eventSignature);
        $this->assertEquals(1.0, $myth->narrativePower);
        $this->assertEquals(0.9, $myth->traumaImprint);
    }

    public function test_generates_miracle_myth_during_high_stability(): void
    {
        $metrics = [
            'fear' => 0.05,
            'trauma' => 0.1,
            'entropy' => 0.2,
            'stability' => 0.95
        ];
        
        $myth = $this->service->evaluateFromZoneMetrics($metrics, ['god_blessing'], 200);

        $this->assertNotNull($myth);
        $this->assertEquals('god_blessing', $myth->eventSignature);
        $this->assertEquals(0.9, $myth->narrativePower); // Miracle power starts at 0.9
        $this->assertEquals(0.95, $myth->moraleImprint);
    }

    public function test_ignores_normal_events(): void
    {
        $metrics = [
            'fear' => 0.3,
            'trauma' => 0.1,
            'entropy' => 0.5,
            'stability' => 0.5
        ];
        
        $myth = $this->service->evaluateFromZoneMetrics($metrics, ['rain'], 300);

        $this->assertNull($myth);
    }

    public function test_myth_distortion_increases_over_generations(): void
    {
        $metrics = ['fear' => 0.95, 'trauma' => 0.8, 'entropy' => 0.9];
        $myth = $this->service->evaluateFromZoneMetrics($metrics, ['volcano'], 1);

        $this->assertEquals(0.0, $myth->distortionFactor);
        $this->assertEquals(1.0, $myth->narrativePower);

        $nextGenMyth = $myth->passToNextGeneration(10);

        $this->assertGreaterThan(0.0, $nextGenMyth->distortionFactor);
        $this->assertLessThan(1.0, $nextGenMyth->narrativePower);
    }
}
