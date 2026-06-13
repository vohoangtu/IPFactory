<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * VerifyLoomSecret — xác thực các endpoint service-to-service giữa backend và
 * NarrativeLoom (Python). Các endpoint này (cấp API key từ pool, webhook ghi
 * chronicle) KHÔNG được mở công khai vì chúng lộ key LLM / cho phép tiêm nội dung.
 *
 * NarrativeLoom phải gửi header `X-Loom-Secret` khớp với `services.loom.shared_secret`
 * (env `LOOM_SHARED_SECRET`). So sánh bằng hash_equals để chống timing attack.
 *
 * Fail-closed: nếu chưa cấu hình secret → từ chối (503) thay vì mở toang.
 */
class VerifyLoomSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.loom.shared_secret', '');

        if ($expected === '') {
            Log::warning('[LoomSecret] LOOM_SHARED_SECRET chưa được cấu hình — từ chối request internal.', [
                'path' => $request->path(),
            ]);

            return response()->json([
                'message' => 'Loom service authentication is not configured.',
            ], 503);
        }

        $provided = (string) $request->header('X-Loom-Secret', '');

        if ($provided === '' || ! hash_equals($expected, $provided)) {
            Log::warning('[LoomSecret] Từ chối request internal không hợp lệ.', [
                'path' => $request->path(),
                'ip'   => $request->ip(),
            ]);

            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
