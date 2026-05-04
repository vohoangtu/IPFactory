<?php

namespace Tests\Unit\Economics;

use App\Modules\World\Entities\Inventory;
use App\Modules\World\ValueObjects\Item;
use App\Modules\World\ValueObjects\TradeOffer;
use App\Modules\World\Entities\NaturalResource;
use Tests\TestCase;

class EconomicsTest extends TestCase
{
    public function test_inventory_enforces_weight_limit(): void
    {
        $inventory = new Inventory('actor1', 10.0); // Max 10kg
        
        $stone = new Item('1', 'stone', 2.0, 5.0); // 2 units * 5kg = 10kg
        $success = $inventory->addItem($stone);
        $this->assertTrue($success);
        
        $extraStone = new Item('2', 'stone', 1.0, 5.0); // 1 unit * 5kg = 5kg
        $success2 = $inventory->addItem($extraStone);
        $this->assertFalse($success2, "Should not be able to carry past 10kg");
    }

    public function test_inventory_merges_items_and_decays_food(): void
    {
        $inventory = new Inventory('actor1', 50.0);
        $food1 = new Item('f1', 'food', 10.0, 0.5, 1.0, 0.05); // 10 units, rate 0.05
        $food2 = new Item('f2', 'food', 5.0, 0.5, 0.5, 0.05);  // 5 units, rate 0.05, quality 0.5

        $inventory->addItem($food1);
        $inventory->addItem($food2);

        $items = $inventory->getItems();
        $this->assertCount(1, $items, "Items should be merged");

        $mergedItem = reset($items);
        $this->assertEquals(15.0, $mergedItem->quantity);
        // Average quality = (1*10 + 0.5*5) / 15 = 12.5 / 15 = 0.8333...
        $this->assertEqualsWithDelta(0.833, $mergedItem->quality, 0.01);

        // Tick decay
        $inventory->tickDecay();
        $this->assertEqualsWithDelta(0.833 - 0.05, $mergedItem->quality, 0.01);
    }

    public function test_harvesting_resource_respects_difficulty_and_weight_limit(): void
    {
        $this->markTestSkipped('HarvestingService was removed during module migration.');
    }

    public function test_crafting_stone_axe_consumes_materials(): void
    {
        $this->markTestSkipped('CraftingService was removed during module migration.');
    }

    public function test_barter_market_resolver_calculates_subjective_value(): void
    {
        $this->markTestSkipped('BarterMarketResolver was removed during module migration.');
    }
}
