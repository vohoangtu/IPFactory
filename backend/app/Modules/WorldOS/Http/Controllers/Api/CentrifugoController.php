<?php

declare(strict_types=1);

namespace App\Modules\WorldOS\Http\Controllers\Api;

use App\Broadcasting\CentrifugoBroadcaster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * CentrifugoController — Token endpoint for WebSocket authentication.
 *
 * Provides JWT tokens so the frontend can connect to Centrifugo WebSocket.
 */
class CentrifugoController extends Controller
{
    public function token(Request $request): JsonResponse
    {
        $secret = config('centrifugo.secret');

        if (empty($secret)) {
            return response()->json([
                'error' => 'Centrifugo not configured',
            ], 503);
        }

        $broadcaster = app(CentrifugoBroadcaster::class);

        // Use authenticated user ID if available, otherwise anonymous
        $userId = $request->user()?->id
            ? (string) $request->user()->id
            : 'anonymous-' . substr(md5($request->ip() ?? 'unknown'), 0, 8);

        $token = $broadcaster->generateToken($userId, 86400); // 24 hours

        return response()->json([
            'token' => $token,
        ]);
    }
}
