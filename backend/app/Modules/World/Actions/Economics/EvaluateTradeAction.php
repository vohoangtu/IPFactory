<?php

namespace App\Modules\World\Actions\Economics;

use App\Modules\World\Entities\Inventory;
use App\Modules\World\ValueObjects\TradeOffer;
use App\Modules\World\Entities\NaturalResource;

/**
 * EvaluateTradeAction: AI xem xét lời đề nghị đổi đồ dựa trên giá trị chủ quan.
 */
class EvaluateTradeAction
implements \App\Contracts\ActionInterface {
    public function execute(
        Inventory $targetInventory,
        TradeOffer $offer,
        float $targetHunger,
        float $targetSafetyNeed,
        float $relationTrust
    ): bool {
        if ($relationTrust < -0.5) {
            return false;
        }

        $gainedValue = 0.0;
        foreach ($offer->giveItems as $itemSnapshot) {
            $gainedValue += $this->calculateSubjectiveValue(
                $itemSnapshot['category'], 
                $itemSnapshot['quantity'], 
                $targetHunger, 
                $targetSafetyNeed
            );
        }

        $lostValue = 0.0;
        foreach ($offer->requestItems as $req) {
            if ($targetInventory->getCategoryTotal($req['category']) < $req['quantity']) {
                return false; 
            }

            $lostValue += $this->calculateSubjectiveValue(
                $req['category'], 
                $req['quantity'], 
                $targetHunger, 
                $targetSafetyNeed
            );
        }

        $requiredProfitMargin = 1.0 - ($relationTrust * 0.1);
        return $gainedValue >= ($lostValue * $requiredProfitMargin);
    }

    private function calculateSubjectiveValue(string $category, float $quantity, float $hunger, float $safetyNeed): float
    {
        $baseValue = $quantity;

        return match ($category) {
            NaturalResource::CATEGORY_FOOD => $baseValue * (1.0 + ($hunger * 5.0)),
            NaturalResource::CATEGORY_WOOD, 
            NaturalResource::CATEGORY_STONE => $baseValue * (1.0 + ($safetyNeed * 3.0)),
            NaturalResource::CATEGORY_MINERAL => $baseValue * 2.0,
            default => $baseValue,
        };
    }
}
