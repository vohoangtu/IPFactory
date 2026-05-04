<?php

namespace App\Modules\World\Actions\Economics;

use App\Modules\World\Entities\NaturalResource;
use App\Modules\World\Entities\Inventory;
use App\Modules\World\ValueObjects\Item;
use Illuminate\Support\Str;

/**
 * HarvestResourceAction: Agent thực hiện hành động khai thác tài nguyên.
 */
class HarvestResourceAction
implements \App\Contracts\ActionInterface {
    public function execute(
        NaturalResource $resource,
        Inventory $inventory,
        float $efficiencyMultiplier = 1.0
    ): float {
        // 1. Tính toán lượng khai thác dựa trên độ khó và hiệu suất của Agent
        $baseRequest = 10.0 * $efficiencyMultiplier;
        $actualRequest = $baseRequest / $resource->harvestDifficulty;

        // 2. Thực hiện khai thác từ thực thể địa lý
        $harvestedQty = $resource->harvest($actualRequest);

        if ($harvestedQty > 0) {
            // 3. Tạo vật phẩm vật lý và đưa vào túi đồ
            $newItem = new Item(
                id: (string) Str::uuid(),
                category: $resource->category,
                quantity: $harvestedQty,
                weightPerUnit: $this->getWeightPerUnit($resource->category),
                quality: 1.0,
                decayRatePerTick: $this->getDecayRate($resource->category)
            );

            if (!$inventory->addItem($newItem)) {
                // Nếu túi đồ đầy, trả lại tài nguyên cho thiên nhiên (Hoặc quăng ra đất)
                $resource->currentAmount += $harvestedQty;
                return 0.0;
            }
        }

        return $harvestedQty;
    }

    private function getWeightPerUnit(string $category): float
    {
        return match ($category) {
            NaturalResource::CATEGORY_WOOD => 0.5,
            NaturalResource::CATEGORY_STONE => 2.0,
            NaturalResource::CATEGORY_FOOD => 0.2,
            NaturalResource::CATEGORY_MINERAL => 1.5,
            default => 1.0,
        };
    }

    private function getDecayRate(string $category): float
    {
        return ($category === NaturalResource::CATEGORY_FOOD) ? 0.02 : 0.0;
    }
}
