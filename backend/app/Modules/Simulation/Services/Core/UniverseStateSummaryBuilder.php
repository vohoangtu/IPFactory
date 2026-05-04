<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Services\Core;

use App\Modules\World\Models\Universe;

class UniverseStateSummaryBuilder
{
    public function build(Universe $universe): array
    {
        $sv = $universe->state_vector;
        if (is_string($sv)) {
            $sv = json_decode($sv, true) ?? [];
        }
        $civilization = $sv['civilization'] ?? [];
        $economy = $civilization['economy'] ?? [];
        $demographic = $civilization['demographic'] ?? [];
        $politics = $civilization['politics'] ?? [];
        $socialGraph = $sv['social_graph'] ?? [];
        $discovery = $civilization['discovery'] ?? null;
        $knowledgeGraph = $sv['knowledge_graph'] ?? [];
        $ideologyConversion = $sv['ideology_conversion'] ?? null;
        $cosmic = $sv['cosmic'] ?? [];
        $meta = $sv['meta'] ?? [];

        return [
            'universe_id' => $universe->id,
            'current_tick' => $universe->current_tick,
            'economy' => $economy,
            'demographic' => $demographic,
            'politics' => $politics,
            'social_graph' => [
                'trust_edges' => is_array($socialGraph['trust'] ?? null) ? count($socialGraph['trust']) : 0,
                'loyalty_edges' => is_array($socialGraph['loyalty'] ?? null) ? count($socialGraph['loyalty']) : 0,
                'rivalry_edges' => is_array($socialGraph['rivalry'] ?? null) ? count($socialGraph['rivalry']) : 0,
                'updated_tick' => $socialGraph['updated_tick'] ?? null,
            ],
            'discovery' => $discovery,
            'knowledge_graph' => [
                'node_count' => is_array($knowledgeGraph['nodes'] ?? null) ? count($knowledgeGraph['nodes']) : 0,
                'edge_count' => is_array($knowledgeGraph['edges'] ?? null) ? count($knowledgeGraph['edges']) : 0,
                'updated_tick' => $knowledgeGraph['updated_tick'] ?? null,
            ],
            'ideology_conversion' => $ideologyConversion,
            'zenith' => [
                'data_mass' => $cosmic['data_mass'] ?? 0,
                'time_dilation' => $cosmic['time_dilation'] ?? 0,
                'time_saliency' => $meta['time_saliency'] ?? 0,
                'active_myths' => count($meta['active_myths'] ?? []),
                'meaning_systems' => count($meta['meaning_systems'] ?? []),
                'mutation_rate' => $meta['rule_mutation_rate'] ?? 0,
            ],
        ];
    }
}
