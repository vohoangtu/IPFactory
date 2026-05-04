<?php

namespace App\Modules\Intelligence\Actions;

use App\Modules\Simulation\Models\BranchEvent;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\World\Models\Universe;
use Illuminate\Support\Collection;

class GetUniverseActorsAction
implements \App\Contracts\ActionInterface {
    /**
     * Lấy danh sách toàn bộ Actor đã từng xuất hiện trong Universe.
     * Trích xuất từ BranchEvent và Chronicles.
     */
    public function execute(int $universeId): Collection
    {
        return \App\Modules\Intelligence\Models\Actor::with('supremeEntity')
            ->where('universe_id', $universeId)
            ->orderBy('is_alive', 'desc') // Alive first
            ->orderBy('id', 'desc')
            ->get();
    }
}

