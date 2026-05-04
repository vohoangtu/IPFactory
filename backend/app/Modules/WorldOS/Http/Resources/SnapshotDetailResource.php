<?php

namespace App\Modules\WorldOS\Http\Resources;

use Illuminate\Http\Request;

class SnapshotDetailResource extends SnapshotResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'state_vector' => $this->state_vector ?? [],
        ]);
    }
}
