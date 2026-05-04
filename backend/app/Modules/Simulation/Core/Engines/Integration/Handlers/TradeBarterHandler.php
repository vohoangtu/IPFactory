<?php

namespace App\Modules\Simulation\Core\Engines\Integration\Handlers;

use App\Modules\Economics\Services\BarterMarketResolver;
use App\Modules\Economics\ValueObjects\TradeOffer;
use App\Modules\Simulation\Core\Entities\Agent;
use App\Modules\Simulation\Core\State\WorldState;

/**
 * 2 AI đứng cùng 1 Tile. AI chủ động tạo TradeOffer dựa trên tài nguyên thừa/thiếu.
 * AI bên kia đánh giá bằng BarterMarketResolver (Subjective Value + Trust).
 */
class TradeBarterHandler implements ActionHandlerInterface
{
    public function __construct(
        private readonly BarterMarketResolver $marketResolver
    ) {}

    public function getActionName(): string
    {
        return 'trade_barter';
    }

    public function canExecute(Agent $agent, WorldState $world): bool
    {
        if ($agent->energy < 5.0) return false;

        // Phải có ít nhất 1 Agent khác cùng Tile
        $neighbors = $this->findNeighbors($agent, $world);
        return count($neighbors) > 0;
    }

    public function execute(Agent $agent, WorldState $world): bool
    {
        $neighbors = $this->findNeighbors($agent, $world);
        if (empty($neighbors)) return false;

        $agent->consumeEnergy(5.0);

        // Chọn neighbor đầu tiên để giao dịch
        /** @var Agent $target */
        $target = $neighbors[0];

        // AI xác định mình thiếu gì nhất
        $agentNeeds = $this->identifyNeed($agent);
        $agentSurplus = $this->identifySurplus($agent);

        if (!$agentNeeds || !$agentSurplus) return false;

        // Tạo Offer: Cho surplus, xin needs
        $offer = new TradeOffer(
            actorId: $agent->id,
            giveItems: [['category' => $agentSurplus['category'], 'quantity' => $agentSurplus['quantity']]],
            requestItems: [['category' => $agentNeeds, 'quantity' => min(5.0, $target->inventory->getCategoryTotal($agentNeeds))]],
            createdAtTick: 0
        );

        // Target đánh giá offer
        $accepted = $this->marketResolver->evaluateOffer(
            $target->inventory,
            $offer,
            $target->hunger,
            max(0, 1.0 - ($target->health / 100.0)), // Safety need
            0.0 // Neutral trust cho POC
        );

        if ($accepted) {
            // Giao dịch thành công
            foreach ($offer->giveItems as $item) {
                $taken = $agent->inventory->takeItemByCategory($item['category'], $item['quantity']);
                if ($taken > 0) {
                    $newItem = new \App\Modules\Economics\ValueObjects\Item(
                        (string) \Illuminate\Support\Str::uuid(), $item['category'], $taken, 1.0
                    );
                    $target->inventory->addItem($newItem);
                }
            }
            foreach ($offer->requestItems as $req) {
                $taken = $target->inventory->takeItemByCategory($req['category'], $req['quantity']);
                if ($taken > 0) {
                    $newItem = new \App\Modules\Economics\ValueObjects\Item(
                        (string) \Illuminate\Support\Str::uuid(), $req['category'], $taken, 1.0
                    );
                    $agent->inventory->addItem($newItem);
                }
            }

            // Tăng Trust giữa 2 AI
            $agent->psychology->applyDelta(['joy' => 0.1, 'fear' => 0.0, 'stress' => -0.1, 'anger' => 0.0, 'sadness' => 0.0]);
            $target->psychology->applyDelta(['joy' => 0.1, 'fear' => 0.0, 'stress' => -0.1, 'anger' => 0.0, 'sadness' => 0.0]);
            return true;
        }

        return false;
    }

    private function findNeighbors(Agent $agent, WorldState $world): array
    {
        $allAgents = $world->get('agents', []);
        $neighbors = [];
        foreach ($allAgents as $other) {
            if ($other->id !== $agent->id && $other->x === $agent->x && $other->y === $agent->y && $other->isAlive()) {
                $neighbors[] = $other;
            }
        }
        return $neighbors;
    }

    private function identifyNeed(Agent $agent): ?string
    {
        // Cái gì thiếu nhất?
        if ($agent->hunger > 0.5 && $agent->inventory->getCategoryTotal('food') < 5) return 'food';
        if ($agent->inventory->getCategoryTotal('wood') < 2) return 'wood';
        if ($agent->inventory->getCategoryTotal('stone') < 2) return 'stone';
        return null;
    }

    private function identifySurplus(Agent $agent): ?array
    {
        // Cái gì thừa nhất?
        $categories = ['food', 'wood', 'stone'];
        $best = null;
        $bestQty = 0;
        foreach ($categories as $cat) {
            $qty = $agent->inventory->getCategoryTotal($cat);
            if ($qty > 3 && $qty > $bestQty) {
                $best = $cat;
                $bestQty = $qty;
            }
        }
        return $best ? ['category' => $best, 'quantity' => min(3.0, $bestQty / 2)] : null;
    }
}
