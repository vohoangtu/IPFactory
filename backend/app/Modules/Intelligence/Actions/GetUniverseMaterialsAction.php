<?php

namespace App\Modules\Intelligence\Actions;

use App\Modules\Simulation\Models\MaterialInstance;
use Illuminate\Support\Collection;

class GetUniverseMaterialsAction
implements \App\Contracts\ActionInterface {
    /**
     * Lấy danh sách vật chất hiện có trong Universe, phân loại theo ontology.
     */
    public function execute(int $universeId): Collection
    {
        return MaterialInstance::where('universe_id', $universeId)
            ->with('material')
            ->get()
            ->groupBy(function ($instance) {
                return $instance->material->ontology;
            })
            ->map(function ($instances, $ontology) {
                return [
                    'ontology' => $ontology,
                    'count' => $instances->count(),
                    'items' => $instances->map(function ($instance) {
                        return [
                            'id' => $instance->id,
                            'name' => $instance->material->name,
                            'slug' => $instance->material->slug,
                            'current_value' => $instance->current_value,
                            'stability' => $instance->stability ?? 1.0,
                        ];
                    }),
                ];
            })
            ->values();
    }
}

