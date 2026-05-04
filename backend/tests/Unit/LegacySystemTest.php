<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Modules\World\Models\Universe;
use App\Modules\Intelligence\Services\LegacySystem;
use App\Modules\Intelligence\Entities\ActorState;
use Illuminate\Support\Facades\Log;
use Mockery;

class LegacySystemTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_8d_culture_legacy_imprint()
    {
        Log::shouldReceive('info')->once();
        
        // Mock Universe to avoid DB connection
        $universe = Mockery::mock(Universe::class)->makePartial();
        $universe->id = 999;
        $universe->state_vector = [];
        $universe->current_tick = 100;
        $universe->shouldReceive('save')->andReturn(true);

        $legacySystem = new LegacySystem();

        $hero = new ActorState(
            id: 1,
            universeId: 999,
            name: "Hero Test",
            archetype: "Scholar",
            traits: ['Curiosity' => 0.95],
            metrics: [
                'culture' => [
                    'survival_grit' => 0.8,
                    'innovation_openness' => 0.9,
                    'aesthetic_value' => 0.7
                ]
            ],
            isAlive: false,
            isHeroic: true,
            heroicType: 'SCIENTIST'
        );

        $legacySystem->imprintLegacy($universe, $hero);
        $legacy = $universe->state_vector['legacy'];

        $this->assertEquals(0.3, $legacy['knowledge_floor'], '', 0.001);
        $this->assertEquals(0.45, $legacy['memetic_imprint']['innovation_openness'], '', 0.001);
        $this->assertEquals(0.4, $legacy['memetic_imprint']['survival_grit'], '', 0.001);
    }

    public function test_institutional_legacy_imprint()
    {
        // Mock Universe
        $universe = Mockery::mock(Universe::class)->makePartial();
        $universe->id = 999;
        $universe->name = "Test Universe";
        $universe->state_vector = [];
        $universe->current_tick = 100;

        $legacySystem = new LegacySystem();

        $inst = [
            'name' => 'Falling Empire',
            'stability' => 0.8,
            'impact_vector' => [
                'power' => 0.5,
                'knowledge' => 0.3
            ]
        ];

        $legacySystem->imprintInstitutionalLegacy($universe, $inst);
        $legacy = $universe->state_vector['legacy'];

        // Expected: 0.5 * 0.8 * 0.4 = 0.16
        $this->assertEquals(0.16, round($legacy['institutional_legacy']['power'], 4));
        $this->assertEquals(0.096, round($legacy['institutional_legacy']['knowledge'], 4));
    }

    public function test_apply_floors()
    {
        $legacySystem = new LegacySystem();
        
        $fields = [
            'survival' => 0.1,
            'reproduction' => 0.1,
            'wealth' => 0.1,
            'power' => 0.1,
            'knowledge' => 0.1,
            'meaning' => 0.1,
            'status' => 0.1,
            'belonging' => 0.1
        ];

        $legacy = [
            'knowledge_floor' => 0.3,
            'institutional_legacy' => [
                'power' => 0.2
            ],
            'memetic_imprint' => [
                'survival_grit' => 0.8
            ],
            'preservation_rate' => 0.4
        ];

        $legacySystem->applyFloors($fields, $legacy);

        // knowledge: max(0.1, 0.3 * 0.4) = 0.12
        $this->assertEquals(0.12, round($fields['knowledge'], 4));
        
        // power: max(0.1, 0.2 * 0.4) = 0.1 (0.08 < 0.1)
        $this->assertEquals(0.1, round($fields['power'], 4));
        
        // survival: max(0.1, 0.8 * 0.4 * 0.5) = 0.16
        $this->assertEquals(0.16, round($fields['survival'], 4));
    }
}
