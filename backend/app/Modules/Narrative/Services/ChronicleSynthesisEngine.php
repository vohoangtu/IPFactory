<?php

namespace App\Modules\Narrative\Services;

use App\Contracts\CausalityGraphServiceInterface;

/**
 * ChronicleSynthesisEngine: Synthesizes causal links into a "Fact Sheet" for LLM analysis.
 */
class ChronicleSynthesisEngine
{
    public function __construct(
        protected CausalityGraphServiceInterface $causalityGraph
    ) {}

    /**
     * Synthesize causal links for a specific tick range.
     */
    public function synthesize(int $universeId, int $fromTick, int $toTick): array
    {
        $links = $this->causalityGraph->getRecentLinksForUniverse($universeId, 100);

        return collect($links)
            ->filter(fn($link) => $link['tick'] >= $fromTick && $link['tick'] <= $toTick)
            ->map(function($link) {
                return $this->formatLinkAsNarrative($link);
            })
            ->values()
            ->toArray();
    }

    protected function formatLinkAsNarrative(array $link): string
    {
        $src = $this->parseEntity($link['src']);
        $tgt = $this->parseEntity($link['tgt']);
        $rel = $link['rel'];
        
        return sprintf(
            "[%s:%s] %s -> [%s:%s] (Prob: %s)",
            ucfirst($src['type']),
            $src['id'],
            strtoupper($rel),
            ucfirst($tgt['type']),
            $tgt['id'],
            $link['meta']['probability'] ?? '1.0'
        );
    }

    protected function parseEntity(string $entityStr): array
    {
        $parts = explode(':', $entityStr);
        return [
            'type' => $parts[0] ?? 'unknown',
            'id'   => $parts[1] ?? 'unknown'
        ];
    }
}
