<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Contracts\SimulationEngine;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseInteraction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * V10+ Vector 6: Multiverse Economic Network Engine 🌌💱
 *
 * "Giữa các vũ trụ song song tồn tại các con đường thương mại lượng tử —
 * nơi tri thức, ý nghĩa và tài nguyên chảy theo gradient entropy."
 *
 * Cơ chế:
 *  1. Quantum Trade Routes — vận chuyển knowledge/meaning giữa u/v entropy đồng bộ
 *  2. Parallel Universe Arbitrage — flow từ vũ trụ giàu sang nghèo (cùng attractor)
 *  3. Multiverse Debt — vũ trụ nhận Seeds nhưng không phát triển → tích nợ nhân quả
 */
class MultiverseEconomyEngine implements SimulationEngine
{
    use DefaultSimulationEnginePhase;

    private const TRADE_ENTROPY_SYNC_THRESHOLD = 0.1;   // ±10% entropy → trade route opens
    private const KNOWLEDGE_FLOW_RATE          = 0.02;
    private const MEANING_FLOW_RATE            = 0.015;
    private const DEBT_ACCUMULATION_RATE       = 0.005;
    private const ARBITRAGE_WEALTH_THRESHOLD   = 0.3;   // wealth diff needed for arbitrage

    public function name(): string
    {
        return 'multiverse_economy';
    }

    public function phase(): string
    {
        return 'meta';
    }

    public function priority(): int
    {
        return 75;
    }

    public function tickRate(): int
    {
        return 10; // Economics run slower
    }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $tick = $ctx->getTick();
        $universeId = $ctx->getUniverseId();
        $multiverseId = (int) $state->get('multiverse_id', 0);
        
        if (!$universeId || !$multiverseId) {
            return new EngineResult([], [], []);
        }

        // ── 1. Identify trade partners ──
        $partners = $this->findTradePartners($universeId, $multiverseId, $state);

        foreach ($partners as $partner) {
            $pState = $partner['state'];

            // ── 2. Quantum Trade: Knowledge flow ──
            $this->exchangeKnowledge($state, $pState, $partner['entropy_diff']);

            // ── 3. Quantum Trade: Meaning flow ──
            $this->exchangeMeaning($state, $pState, $partner['entropy_diff']);

            // ── 4. Arbitrage: Wealth equalisation ──
            $this->processArbitrage($state, $pState, $partner['wealth_diff']);
        }

        // ── 5. Multiverse Debt processing ──
        $this->processMultiverseDebt($state, $tick);

        return new EngineResult([], [], []);
    }

    /**
     * Find universes in the same multiverse with synced entropy (within threshold).
     * Returns lightweight partner data including their WorldState proxy.
     */
    private function findTradePartners(int $universeId, int $multiverseId, WorldState $state): array
    {
        $myEntropy  = (float) $state->get('entropy', 0.5);
        $myAttractor = $state->getActiveAttractor();
        $myWealth   = (float) $state->get('fields.wealth', 0.0);

        // Cache for 10 ticks to avoid repeated DB queries
        $cacheKey = "multiverse_trade_partners_{$multiverseId}_{$universeId}";
        $universes = cache()->remember($cacheKey, 30, function () use ($multiverseId, $universeId) {
            return Universe::query()
                ->where('multiverse_id', $multiverseId)
                ->where('id', '!=', $universeId)
                ->where('status', 'active')
                ->get(['id', 'state_vector', 'current_tick'])
                ->toArray();
        });

        $partners = [];
        foreach ($universes as $u) {
            $sv = is_string($u['state_vector']) ? json_decode($u['state_vector'], true) : ($u['state_vector'] ?? []);
            $pEntropy   = (float) ($sv['entropy'] ?? 0.5);
            $pAttractor = $sv['active_attractor'] ?? 'unknown';
            $pWealth    = (float) ($sv['fields']['wealth'] ?? 0.0);

            $entropyDiff = abs($myEntropy - $pEntropy);
            $wealthDiff  = $myWealth - $pWealth;

            if ($entropyDiff <= self::TRADE_ENTROPY_SYNC_THRESHOLD) {
                $partners[] = [
                    'universe_id'   => $u['id'],
                    'entropy_diff'  => $entropyDiff,
                    'wealth_diff'   => $wealthDiff,
                    'same_attractor' => $pAttractor === $myAttractor,
                    'partner_sv'    => $sv,
                    'state'         => WorldState::fromArray(array_merge(
                        $sv,
                        ['universe_id' => $u['id']]
                    )),
                ];
            }
        }

        return $partners;
    }

    /**
     * Exchange knowledge field between two universes.
     * Knowledge flows from high-entropy (more chaotic = more discovery) to low.
     */
    private function exchangeKnowledge(WorldState $mine, WorldState $partner, float $entropyDiff): void
    {
        $myKnowledge  = (float) $mine->get('fields.knowledge',    0.0);
        $pKnowledge   = (float) $partner->get('fields.knowledge', 0.0);
        $myEntropy    = (float) $mine->get('entropy', 0.5);
        $pEntropy     = (float) $partner->get('entropy', 0.5);

        // Knowledge flows from higher entropy source (exploration pressure)
        $flow = self::KNOWLEDGE_FLOW_RATE * (1 - $entropyDiff * 10);

        if ($myEntropy > $pEntropy) {
            // We give knowledge (chaos → order transfer)
            $mine->set('fields.knowledge', max(0, $myKnowledge - $flow * 0.5));
            $mine->set('meta.multiverse_knowledge_exported', (float)$mine->get('meta.multiverse_knowledge_exported', 0) + $flow);
        } else {
            // We receive knowledge
            $mine->set('fields.knowledge', min(1.0, $myKnowledge + $flow));
            $mine->set('meta.multiverse_knowledge_imported', (float)$mine->get('meta.multiverse_knowledge_imported', 0) + $flow);
        }
    }

    /**
     * Exchange meaning resonance between universes.
     */
    private function exchangeMeaning(WorldState $mine, WorldState $partner, float $entropyDiff): void
    {
        $myMeaning = (float) $mine->get('fields.meaning', 0.0);
        $pMeaning  = (float) $partner->get('fields.meaning', 0.0);

        $diff = $pMeaning - $myMeaning;
        if (abs($diff) < 0.05) {
            return; // No significant gradient
        }

        $flow = self::MEANING_FLOW_RATE * min(1.0, abs($diff) * 5);
        if ($diff > 0) {
            $mine->set('fields.meaning', min(1.0, $myMeaning + $flow));
            $mine->set('meta.meaning_resonance_received', true);
        }
    }

    /**
     * Wealth arbitrage between same-attractor universes.
     */
    private function processArbitrage(WorldState $mine, WorldState $partner, float $wealthDiff): void
    {
        if (!($partner->getActiveAttractor() === $mine->getActiveAttractor())) {
            return;
        }

        if (abs($wealthDiff) < self::ARBITRAGE_WEALTH_THRESHOLD) {
            return;
        }

        $myWealth = (float) $mine->get('fields.wealth', 0.0);
        $arbitrageFlow = 0.005 * ($wealthDiff > 0 ? 1 : -1);

        // Wealth equalizes toward the poorer universe (diminishing inequality)
        $mine->set('fields.wealth', max(0, min(1.0, $myWealth - $arbitrageFlow)));
        $mine->set('meta.multiverse_arbitrage_active', true);
    }

    /**
     * Accumulate or resolve multiverse debt.
     * A universe that has received Meaning Seeds but fails to develop them accumulates debt.
     */
    private function processMultiverseDebt(WorldState $state, int $tick): void
    {
        $inheritedAttractor = $state->get('meta.inherited_attractor', null);
        if (!$inheritedAttractor) {
            return; // No seeds received, no debt
        }

        $currentAttractor = $state->getActiveAttractor();
        $currentDebt      = (float) $state->get('meta.multiverse_debt', 0.0);
        $stability        = (float) $state->get('stability_index', 1.0);

        if ($currentAttractor === $inheritedAttractor || $stability > 0.7) {
            // Universe is developing well → reduce debt
            $state->set('meta.multiverse_debt', max(0, $currentDebt - self::DEBT_ACCUMULATION_RATE * 2));
        } else {
            // Universe is stagnating → accumulate debt
            $newDebt = min(1.0, $currentDebt + self::DEBT_ACCUMULATION_RATE);
            $state->set('meta.multiverse_debt', $newDebt);

            // High debt → entropy pressure (the universe is "haunted" by its past)
            if ($newDebt > 0.5) {
                $entropy = (float) $state->get('entropy', 0.5);
                $state->set('entropy', min(1.0, $entropy + 0.002));

                if ($tick % 50 === 0) {
                    Log::info("MultiverseEconomyEngine: High multiverse debt ({$newDebt}) — universe haunted by unfulfilled legacy.", [
                        'universe_id'        => $state->get('universe_id'),
                        'inherited_attractor' => $inheritedAttractor,
                        'current_attractor'  => $currentAttractor,
                    ]);
                }
            }
        }
    }
}

