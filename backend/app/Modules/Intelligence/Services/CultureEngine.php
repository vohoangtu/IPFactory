<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\Intelligence\Contracts\ActorRepositoryInterface;
use App\Modules\World\Models\Universe;
use Illuminate\Support\Facades\Log;

/**
 * Culture Engine (Tier 7).
 * Meme pool (survival, social, technology, ritual), transmission (peer, observation),
 * cultural selection (fitness), mutation/drift. Culture groups (shared memes → cohesion).
 * Feedback to behavior via culture_weight in decision score.
 */
class CultureEngine
{
    public const MEME_SURVIVAL = 'survival_grit';
    public const MEME_REPRODUCTION = 'reproductive_norms';
    public const MEME_WEALTH = 'mercantile_ethic';
    public const MEME_POWER = 'violence_tolerance';
    public const MEME_KNOWLEDGE = 'innovation_openness';
    public const MEME_MEANING = 'ritual_rigidity';
    public const MEME_STATUS = 'aesthetic_value';
    public const MEME_BELONGING = 'collectivism_index';

    public const MEME_DIMENSIONS = [
        self::MEME_SURVIVAL, self::MEME_REPRODUCTION, self::MEME_WEALTH, self::MEME_POWER,
        self::MEME_KNOWLEDGE, self::MEME_MEANING, self::MEME_STATUS, self::MEME_BELONGING
    ];

    public function __construct(
        protected ActorRepositoryInterface $actorRepository,
        protected EvolutionPressureService $evolutionPressure
    ) {}

    public function runWithState(\App\Modules\Simulation\Core\Runtime\State\WorldState $state, int $currentTick): void
    {
        $interval = (int) config('worldos.intelligence.culture_tick_interval', 10);
        if ($interval <= 0 || $currentTick % $interval !== 0) {
            return;
        }

        $universeId = (int) $state->get('universe_id');
        $seed = (int) $state->get('seed', 0);

        $alive = array_values(array_filter($state->getActorEntities(), fn($a) => $a->isAlive));
        if (count($alive) < 2) {
            $this->ensureCultureInitialized($alive, $universeId, $currentTick);
            return;
        }

        $transmissionRate = (float) config('worldos.intelligence.culture_transmission_rate', 0.15);
        $mutationRate = (float) config('worldos.intelligence.culture_mutation_rate', 0.05);
        $barrierThreshold = (float) config('worldos.intelligence.linguistic_barrier', 0.4);

        $seedBase = $seed + $universeId * 31;
        
        $pressure = $state->get('ecosystem.pressure', []);
        if (empty($pressure)) {
             $pressure = $this->evolutionPressure->fromUniverseId($universeId); 
        }

        $updatedCount = 0;
        foreach ($alive as $actor) {
            $this->ensureCultureInitializedForActor($actor, $currentTick);
            $fitness = $this->evolutionPressure->fitness(
                $actor->traits ?? [],
                $actor->metrics['physic'] ?? null,
                $pressure
            );
            
            $actorId = (int) ($actor->id ?? 0);
            $rng = $this->detFloat($seedBase, $currentTick, $actorId, 0);
            
            if ($rng < $transmissionRate) {
                $others = array_values(array_filter($alive, fn($a) => (int)($a->id ?? 0) !== $actorId));
                if (empty($others)) {
                    $this->applyDrift($actor, $mutationRate * 0.5, $seedBase, $currentTick);
                    $actor->metrics['culture_group'] = $this->cultureGroupId($actor->metrics['culture'] ?? []);
                    $updatedCount++;
                    continue;
                }

                $peerIndexRng = $this->detFloat($seedBase, $currentTick, $actorId, 1);
                $peer = $others[(int) ($peerIndexRng * count($others)) % count($others)];
                $this->ensureCultureInitializedForActor($peer, $currentTick);

                $dist = $this->calculateCulturalDistance($actor->metrics['culture'], $peer->metrics['culture']);
                $effectiveRate = ($dist > $barrierThreshold) ? $transmissionRate * 0.1 : $transmissionRate;

                if ($this->detFloat($seedBase, $currentTick, $actorId, 2) < $effectiveRate) {
                    $peerFitness = $this->evolutionPressure->fitness(
                        $peer->traits ?? [],
                        $peer->metrics['physic'] ?? null,
                        $pressure
                    );
                    
                    if ($peerFitness >= $fitness * 0.8) {
                        $this->copyMemeWithMutation($actor, $peer, $mutationRate, $seedBase, $currentTick);
                    }
                }
            }
            $this->applyDrift($actor, $mutationRate * 0.5, $seedBase, $currentTick);
            $actor->metrics['culture_group'] = $this->cultureGroupId($actor->metrics['culture'] ?? []);
            $updatedCount++;
        }

        if ($updatedCount > 0) {
            Log::debug("CultureEngine: Universe {$universeId} tick {$currentTick}, {$updatedCount} actors culture updated in state pool");
        }
    }

    public function evaluate(Universe $universe, int $currentTick): void
    {
        // Deprecated
    }

    private function calculateCulturalDistance(array $c1, array $c2): float
    {
        $sum = 0;
        foreach (self::MEME_DIMENSIONS as $d) {
            $sum += pow(($c1[$d] ?? 0.5) - ($c2[$d] ?? 0.5), 2);
        }
        return sqrt($sum) / sqrt(count(self::MEME_DIMENSIONS));
    }

    private function ensureCultureInitialized(array $alive, int $universeId, int $tick): void
    {
        foreach ($alive as $actor) {
            $this->ensureCultureInitializedForActor($actor, $tick);
            $this->actorRepository->save($actor);
        }
    }

    private function ensureCultureInitializedForActor($actor, int $tick): void
    {
        $culture = $actor->metrics['culture'] ?? null;
        if (is_array($culture) && count($culture) >= 8) {
            return;
        }
        $traits = $actor->traits ?? [];
        $physic = $actor->metrics['physic'] ?? [];
        
        $culture = [
            self::MEME_SURVIVAL     => ($traits[10] ?? 0.5), // Resilience/RiskTolerance
            self::MEME_REPRODUCTION => ($physic[0] ?? 0.5),   // Vitality
            self::MEME_WEALTH       => ($traits[7]  ?? 0.5), // Pragmatism
            self::MEME_POWER        => ($traits[0]  ?? 0.5), // Dominance
            self::MEME_KNOWLEDGE    => ($traits[8]  ?? 0.5), // Curiosity
            self::MEME_MEANING      => ($traits[13] ?? 0.5), // Hope
            self::MEME_STATUS       => ($traits[15] ?? 0.5), // Pride
            self::MEME_BELONGING    => ($traits[5]  ?? 0.5), // Solidarity
        ];
        $actor->metrics['culture'] = $culture;
    }

    private function copyMemeWithMutation($receiver, $donor, float $mutationRate, int $seed, int $tick): void
    {
        $dim = self::MEME_DIMENSIONS[(int) ($this->detFloat($seed, $tick, ($receiver->id ?? 0) + 100, 2) * count(self::MEME_DIMENSIONS)) % count(self::MEME_DIMENSIONS)];
        $receiverCulture = $receiver->metrics['culture'] ?? $this->defaultCulture();
        $donorCulture = $donor->metrics['culture'] ?? $this->defaultCulture();
        $value = (float) ($donorCulture[$dim] ?? 0.5);
        $delta = ($this->detFloat($seed, $tick, ($receiver->id ?? 0) + 200, 3) * 2 - 1) * $mutationRate;
        $receiverCulture[$dim] = max(0.0, min(1.0, $value + $delta));
        $receiver->metrics['culture'] = $receiverCulture;
    }

    private function applyDrift($actor, float $rate, int $seed, int $tick): void
    {
        $culture = $actor->metrics['culture'] ?? $this->defaultCulture();
        foreach (self::MEME_DIMENSIONS as $i => $dim) {
            $delta = ($this->detFloat($seed, $tick, ($actor->id ?? 0) + 300 + $i, 4) * 2 - 1) * $rate;
            $culture[$dim] = max(0.0, min(1.0, ($culture[$dim] ?? 0.5) + $delta));
        }
        $actor->metrics['culture'] = $culture;
    }

    private function cultureGroupId(array $culture): string
    {
        $bins = [];
        foreach (self::MEME_DIMENSIONS as $d) {
            $v = (float) ($culture[$d] ?? 0.5);
            $bins[] = (int) min(3, floor($v * 4));
        }
        return 'C' . substr(md5(json_encode($bins)), 0, 8);
    }

    private function defaultCulture(): array
    {
        return array_fill_keys(self::MEME_DIMENSIONS, 0.5);
    }

    private function detFloat(int $seed, int $tick, int $salt, int $extra): float
    {
        $h = crc32($seed . ':' . $tick . ':' . $salt . ':' . $extra);
        return (float) (($h & 0x7FFFFFFF) / 0x7FFFFFFF);
    }

    /**
     * Return culture vector for an actor (for Behavior Engine). Keys: survival, social, technology, ritual.
     */
    public static function getCultureForActor(array $metrics): array
    {
        $culture = $metrics['culture'] ?? null;
        if (!is_array($culture)) {
            return array_fill_keys(self::MEME_DIMENSIONS, 0.5);
        }
        $out = [];
        foreach (self::MEME_DIMENSIONS as $d) {
            $out[$d] = max(0.0, min(1.0, (float) ($culture[$d] ?? 0.5)));
        }
        return $out;
    }
}


