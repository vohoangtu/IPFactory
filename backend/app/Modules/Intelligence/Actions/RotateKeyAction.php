<?php

namespace App\Modules\Intelligence\Actions;

use App\Modules\Intelligence\Models\AiKeyPool;
use Illuminate\Support\Facades\Log;

class RotateKeyAction
implements \App\Contracts\ActionInterface {
    public function handle(
        string $requiredTier = 'any',
        ?string $provider = null,
        ?string $modelGroup = null,
        ?string $model = null
    ): ?AiKeyPool {
        $query = AiKeyPool::active();

        if ($provider) {
            $query->where('provider', $provider);
        }

        if ($modelGroup) {
            $query->where('model_group', $modelGroup);
        }

        if ($model) {
            $query->where(function ($q) use ($model) {
                $q->where(\Illuminate\Support\Facades\DB::raw("metadata->>'model'"), '=', $model)
                  ->orWhereRaw("metadata->>'model' IS NULL")
                  ->orWhereRaw("metadata::text = '[]'");
            });
        }

        if ($requiredTier !== 'any') {
            $query->where('tier', $requiredTier);
        }

        $key = $query->orderByRaw("CASE WHEN tier = 'free' THEN 0 ELSE 1 END")
            ->orderBy('level', 'asc')
            ->orderBy('last_used_at', 'asc')
            ->first();

        if (!$key) {
            Log::warning(sprintf(
                'No available AI key found for tier=%s provider=%s model_group=%s model=%s',
                $requiredTier,
                $provider ?? 'any',
                $modelGroup ?? 'any',
                $model ?? 'any'
            ));
        }

        return $key;
    }
}
