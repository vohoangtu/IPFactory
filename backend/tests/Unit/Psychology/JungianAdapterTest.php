<?php

namespace Tests\Unit\Psychology;

use App\Modules\Psychology\Services\JungianBehaviorAdapter;
use Tests\TestCase;

class JungianAdapterTest extends TestCase
{
    private JungianBehaviorAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new JungianBehaviorAdapter();
    }

    public function test_village_elder_injects_cooperate_bias(): void
    {
        $baseContext = ['fear' => 0.5, 'stress' => 0.5];
        $newContext = $this->adapter->injectArchetypeBiases($baseContext, 'VillageElder');

        $this->assertEquals(0.5, $newContext['fear']);
        $this->assertEquals(0.4, $newContext['archetype_cooperate_bias']);
        $this->assertEquals(-0.3, $newContext['archetype_resist_bias']);
    }

    public function test_warlord_injects_aggressive_bias(): void
    {
        $baseContext = [];
        $newContext = $this->adapter->injectArchetypeBiases($baseContext, 'Warlord');

        $this->assertEquals(0.6, $newContext['archetype_resist_bias']);
        $this->assertEquals(-0.5, $newContext['archetype_withdraw_bias']);
        $this->assertEquals(-0.2, $newContext['archetype_cooperate_bias']);
    }

    public function test_unknown_archetype_injects_zero_biases(): void
    {
        $baseContext = [];
        $newContext = $this->adapter->injectArchetypeBiases($baseContext, 'Peasant');

        $this->assertEquals(0.0, $newContext['archetype_resist_bias']);
        $this->assertEquals(0.0, $newContext['archetype_cooperate_bias']);
    }
}
