<?php

namespace App\Modules\Narrative\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Narrative\Actions\GetOmenContextAction;
use Illuminate\Http\JsonResponse;

class NarrativeController extends Controller
{
    public function __construct(
        protected GetOmenContextAction $getOmenContextAction
    ) {}

    public function omenContext(int $universeId): JsonResponse
    {
        $context = $this->getOmenContextAction->handle($universeId);

        return response()->json($context);
    }
}
