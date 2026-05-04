<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * For SSE/EventSource: browser cannot send Authorization header.
 * If request has ?token=..., copy to Authorization: Bearer so auth:sanctum can authenticate.
 */
class SseTokenFromQuery
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->query('token');
        if ($token !== null && $token !== '' && ! $request->header('Authorization')) {
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }

        return $next($request);
    }
}
