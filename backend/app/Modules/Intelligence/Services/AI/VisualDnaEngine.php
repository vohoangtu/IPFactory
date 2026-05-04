<?php

namespace App\Modules\Intelligence\Services\AI;

use App\Modules\Intelligence\Models\LegendaryAgent;
use App\Modules\Simulation\Models\VisualBranch;
use App\Modules\Simulation\Models\VisualMutation;
use Illuminate\Support\Facades\Log;

/**
 * VisualDnaEngine: The core engine for stateful visual consistency and mutation (§V13).
 * Manages Mythic Genomes, Mutation Trees, and Universal Bifurcation.
 */
class VisualDnaEngine
{
    /**
     * Generate or retrieve the root DNA for a legendary agent.
     */
    public function getOrCreateRootDna(LegendaryAgent $legend): array
    {
        // Try to find the root branch
        $root = $legend->universe->visualBranches()
            ->where('legendary_agent_id', $legend->id)
            ->whereNull('parent_branch_id')
            ->first();

        if ($root) return $root->visual_dna;

        // Deterministic DNA Generation
        $hash = hash('xxh128', $legend->original_agent_id . $legend->archetype);
        
        $dna = [
            'mythic_affinity' => $this->resolveAffinity($hash),
            'form_signature' => $this->resolveForm($hash),
            'aura_frequency' => $this->resolveFrequency($hash),
            'symbolic_mark' => $this->resolveMark($hash),
            'color_dominance' => $this->resolveColors($hash),
            'entropy_resistance' => hexdec(substr($hash, 0, 2)) % 101, // 0-100
        ];

        VisualBranch::create([
            'legendary_agent_id' => $legend->id,
            'visual_dna' => $dna,
            'fork_tick' => $legend->tick_discovered,
            'fork_reason' => 'Genesis',
        ]);

        return $dna;
    }

    /**
     * Apply mutation based on environmental pressure (Corruption, Ascension).
     */
    public function applyMutation(VisualBranch $branch, string $type, int $severity, int $tick): ?VisualBranch
    {
        $dna = $branch->visual_dna;
        $resistance = $dna['entropy_resistance'] ?? 50;
        
        $pressure = log($severity + 1);
        $force = ($pressure * 10) - $resistance;

        if ($force <= 0) {
            return $branch; // Immune to this level of pressure
        }

        // Determine if we should fork instead of mutate
        if ($force > 45 && $this->isOntologicalMutation($branch)) {
             return $this->fork($branch, $type, $force, $tick);
        }

        // Apply linear mutation
        $modifiers = $this->calculateModifiers($branch, $force);
        
        VisualMutation::create([
            'visual_branch_id' => $branch->id,
            'type' => $type,
            'severity' => (int)$force,
            'modifiers' => $modifiers,
            'tick' => $tick,
            'trigger_event' => "External Pressure: {$type}"
        ]);

        return $branch;
    }

    protected function fork(VisualBranch $parent, string $reason, float $force, int $tick): VisualBranch
    {
        Log::info("MYTHOS: Visual Fork triggered for Branch #{$parent->id}. Reason: {$reason}, Force: {$force}");

        $childDna = $parent->visual_dna;
        // Major DNA rewrite during fork
        $childDna['mythic_affinity'] = "corrupted_" . $childDna['mythic_affinity'];
        $childDna['entropy_resistance'] = max(5, $childDna['entropy_resistance'] - 20);

        return VisualBranch::create([
            'legendary_agent_id' => $parent->legendary_agent_id,
            'parent_branch_id' => $parent->id,
            'visual_dna' => $childDna,
            'fork_tick' => $tick,
            'fork_reason' => "Bifurcation: {$reason} (Force: " . round($force, 1) . ")",
        ]);
    }

    protected function resolveAffinity(string $hash): string
    {
        $values = ['fire', 'void', 'order', 'chaos', 'nature', 'arcane'];
        return $values[hexdec(substr($hash, 2, 1)) % count($values)];
    }

    protected function resolveForm(string $hash): string
    {
        $values = ['humanoid', 'draconic', 'ascetic', 'ethereal', 'monstrous'];
        return $values[hexdec(substr($hash, 3, 1)) % count($values)];
    }

    protected function resolveFrequency(string $hash): string
    {
        $values = ['low', 'harmonic', 'unstable', 'pulsing'];
        return $values[hexdec(substr($hash, 4, 1)) % count($values)];
    }

    protected function resolveMark(string $hash): string
    {
        $values = ['scar', 'rune', 'eye_mark', 'sigil', 'none'];
        return $values[hexdec(substr($hash, 5, 1)) % count($values)];
    }

    protected function resolveColors(string $hash): string
    {
        $values = ['red-black', 'silver-blue', 'gold-white', 'purple-void', 'green-earth'];
        return $values[hexdec(substr($hash, 6, 1)) % count($values)];
    }

    protected function isOntologicalMutation(VisualBranch $branch): bool
    {
        // Simple heuristic: if the branch has many mutations, it's unstable
        return $branch->mutations()->count() > 3;
    }

    protected function calculateModifiers(VisualBranch $branch, float $force): array
    {
        $mods = [];
        if ($force > 10) $mods['aura_intensity'] = "increased " . round($force / 10, 1) . "x";
        if ($force > 20) $mods['color_shift'] = "darker hue";
        return $mods;
    }
}

