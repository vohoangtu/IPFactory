<?php

namespace App\Modules\WorldOS\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\WorldOS\Actions\GetTickAnalyticsAction;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly GetTickAnalyticsAction $getTickAnalyticsAction
    ) {}

    public function getTickAnalytics(): JsonResponse
    {
        $data = $this->getTickAnalyticsAction->handle();
        return response()->json($data);
    }
}
