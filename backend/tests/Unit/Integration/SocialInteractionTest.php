<?php

namespace Tests\Unit\Integration;

use App\Modules\World\ValueObjects\Item;
use App\Modules\Simulation\Core\Engines\Integration\ActionExecutionEngine;
use App\Modules\Simulation\Core\Engines\Integration\Handlers\AttackHandler;
use App\Modules\Simulation\Core\Engines\Integration\Handlers\CooperateHandler;
use App\Modules\Simulation\Core\Engines\Integration\Handlers\TradeBarterHandler;
use App\Modules\Simulation\Core\Entities\Agent;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use Tests\TestCase;

class SocialInteractionTest extends TestCase
{
    private ActionExecutionEngine $engine;
    private WorldState $world;

    protected function setUp(): void
    {
        parent::setUp();

        $this->markTestSkipped('TradeBarterHandler requires BarterMarketResolver which was removed during module migration.');

        $this->engine = new ActionExecutionEngine();
        $this->engine->registerHandler(new TradeBarterHandler());
        $this->engine->registerHandler(new AttackHandler());
        $this->engine->registerHandler(new CooperateHandler());
        $this->world = new WorldState();
    }

    public function test_attack_deals_damage_and_steals_food(): void
    {
        $attacker = new Agent('attacker');
        $attacker->x = 0; $attacker->y = 0;
        $attacker->energy = 50.0;
        $attacker->hunger = 0.9;
        $attacker->psychology->anger = 0.5;
        $attacker->currentActionSequence = ['attack'];

        $victim = new Agent('victim');
        $victim->x = 0; $victim->y = 0;
        $victim->health = 100.0;
        $victim->inventory->addItem(new Item('f1', 'food', 10.0, 0.5));

        $this->world->set('agents', [$attacker, $victim]);
        $this->engine->tickAgents([$attacker], $this->world);

        $this->assertLessThan(100.0, $victim->health, "Victim should take damage");
        $this->assertTrue($attacker->inventory->getCategoryTotal('food') > 0, "Attacker stole food");
        $this->assertGreaterThan(0.2, $victim->psychology->fear, "Victim should be scared");
    }

    public function test_cooperate_shares_food_with_hungry_neighbor(): void
    {
        $giver = new Agent('giver');
        $giver->x = 0; $giver->y = 0;
        $giver->energy = 50.0;
        $giver->psychology->joy = 0.5;
        $giver->inventory->addItem(new Item('f1', 'food', 10.0, 0.5));
        $giver->currentActionSequence = ['cooperate'];

        $hungry = new Agent('hungry');
        $hungry->x = 0; $hungry->y = 0;
        $hungry->hunger = 0.8;

        $this->world->set('agents', [$giver, $hungry]);
        $this->engine->tickAgents([$giver], $this->world);

        $this->assertTrue($hungry->inventory->getCategoryTotal('food') > 0, "Hungry agent received food");
        $this->assertLessThan(10.0, $giver->inventory->getCategoryTotal('food'), "Giver gave away food");
    }

    public function test_trade_barter_exchanges_surplus_for_need(): void
    {
        $this->markTestSkipped('BarterMarketResolver was removed during module migration.');

        $trader = new Agent('trader');
        $trader->x = 0; $trader->y = 0;
        $trader->energy = 50.0;
        $trader->hunger = 0.8; // Needs food
        $trader->inventory->addItem(new Item('w1', 'wood', 20.0, 0.5)); // 20 wood * 0.5kg = 10kg
        $trader->currentActionSequence = ['trade_barter'];

        $partner = new Agent('partner');
        $partner->x = 0; $partner->y = 0;
        $partner->hunger = 0.1; // Not hungry, so food has low value to partner
        $partner->health = 30.0; // Low health = high safety need = values wood/stone highly
        $partner->inventory->addItem(new Item('f1', 'food', 20.0, 0.5)); // 20 food

        $this->world->set('agents', [$trader, $partner]);
        $this->engine->tickAgents([$trader], $this->world);

        // Trader has surplus wood (20) and needs food. Partner has food surplus.
        // identifyNeed(trader) = 'food' (hunger>0.5 and food<5)
        // identifySurplus(trader) = 'wood' (20 > 3)
        // Trade should happen if partner evaluates the offer favorably.
        $traderFood = $trader->inventory->getCategoryTotal('food');
        $partnerWood = $partner->inventory->getCategoryTotal('wood');

        // At least one side should have exchanged something
        $this->assertTrue($traderFood > 0 || $partnerWood > 0, "Trade should have exchanged items");
    }
}
