<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Modules\World\Models\World;
use App\Modules\Intelligence\Entities\Archetypes\VillageElder;
use App\Modules\Intelligence\Entities\Archetypes\TribalLeader;
use App\Modules\Intelligence\Entities\Archetypes\Warlord;

class TraitSystemTest extends TestCase
{
    public function test_village_elder_eligibility()
    {
        $elder = new VillageElder();
        
        // VillageElder is always eligible (returns true)
        $world = new World();
        $this->assertTrue($elder->isEligible($world));
    }

    public function test_tribal_leader_utility()
    {
        $leader = new TribalLeader();
        
        // TribalLeader attractor: culture=0.8, militarism=0.4, tradition=0.5, stability=-0.1
        // High culture state → stronger utility
        $highCultureState = ['culture' => 0.9, 'militarism' => 0.5, 'tradition' => 0.8, 'stability' => 0.3];
        $lowCultureState = ['culture' => 0.1, 'militarism' => 0.1, 'tradition' => 0.1, 'stability' => 0.9];
        
        $this->assertGreaterThan(
            $leader->getBaseUtility($lowCultureState),
            $leader->getBaseUtility($highCultureState)
        );
    }

    public function test_warlord_utility()
    {
        $warlord = new Warlord();
        
        // Warlord attractor: militarism=0.9, chaos=0.7, stability=-0.5, trauma=0.4
        // In chaotic world: militarism=0.9, chaos=0.9, stability=0.1, trauma=0.5
        $chaoticState = ['militarism' => 0.9, 'chaos' => 0.9, 'stability' => 0.1, 'trauma' => 0.5];
        // score = 0.9*0.9 + 0.9*0.7 + 0.1*(-0.5) + 0.5*0.4 = 0.81 + 0.63 - 0.05 + 0.20 = 1.59
        $this->assertEqualsWithDelta(1.59, $warlord->getBaseUtility($chaoticState), 0.01);
        
        // In stable world: militarism=0.1, chaos=0.1, stability=0.9, trauma=0.0
        $stableState = ['militarism' => 0.1, 'chaos' => 0.1, 'stability' => 0.9, 'trauma' => 0.0];
        // score = 0.1*0.9 + 0.1*0.7 + 0.9*(-0.5) + 0.0*0.4 = 0.09 + 0.07 - 0.45 + 0 = -0.29
        $this->assertEqualsWithDelta(-0.29, $warlord->getBaseUtility($stableState), 0.01);
    }
}
