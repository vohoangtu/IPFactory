<?php

namespace App\Modules\Intelligence\Actions;

use App\Modules\Intelligence\Models\AiKeyPool;

class ReportKeyUsageAction
implements \App\Contracts\ActionInterface {
    public function handle(AiKeyPool $key, ?int $errorCode = null, array $metadata = []): void
    {
        $key->increment('usage_count');
        $key->last_used_at = now();

        if ($errorCode === 401) {
            $key->status = 'inactive';
            $key->cooldown_until = null;
        } elseif ($errorCode === 429) {
            $cooldownMinutes = ($key->tier === 'free') ? 60 : 15;
            $key->cooldown_until = now()->addMinutes($cooldownMinutes);
            $key->status = 'cooldown';
        } elseif ($key->status === 'cooldown' && (!$key->cooldown_until || $key->cooldown_until <= now())) {
            $key->status = 'active';
        }

        if (!empty($metadata)) {
            $key->metadata = array_merge($key->metadata ?? [], $metadata);
        }

        $key->save();
    }
}
