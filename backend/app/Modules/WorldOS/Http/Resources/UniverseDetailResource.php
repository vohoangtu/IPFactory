<?php

namespace App\Modules\WorldOS\Http\Resources;

use Illuminate\Http\Request;

class UniverseDetailResource extends UniverseSummaryResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'state_vector' => $this->state_vector ?? [],
            'axioms' => $this->axioms ?? [],
        ]);
    }
}
