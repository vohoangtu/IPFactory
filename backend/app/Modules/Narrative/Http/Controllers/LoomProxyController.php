<?php

namespace App\Modules\Narrative\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LoomProxyController extends Controller
{
    /**
     * Headers that must not be forwarded to avoid connection issues.
     */
    private array $skipHeaders = [
        'host', 'connection', 'content-length', 'content-encoding',
        'transfer-encoding', 'accept-encoding', 'cookie',
    ];

    /**
     * Forward request to NarrativeLoom service.
     */
    public function proxy(string $path, Request $request): JsonResponse
    {
        // Defense-in-depth: chặn path traversal (route regex đã loại '.', đây là lớp 2).
        if (str_contains($path, '..') || str_contains($path, '://')) {
            return response()->json(['ok' => false, 'error' => 'Invalid loom path'], 400);
        }

        $loomUrl = rtrim((string) config('services.loom.url', 'http://narrative_loom:8001'), '/');
        $url = $loomUrl . '/' . ltrim($path, '/');

        // Build safe headers
        $headers = [];
        foreach ($request->headers->all() as $name => $values) {
            $lower = strtolower($name);
            if (in_array($lower, $this->skipHeaders, true)) {
                continue;
            }
            $headers[$name] = is_array($values) ? implode(', ', $values) : $values;
        }

        // Forward JSON body if present, otherwise form params
        $body = $request->isJson() ? $request->json()->all() : $request->all();

        try {
            $http = Http::timeout(120)
                ->withHeaders($headers)
                ->asJson();

            $method = strtolower($request->method());
            $response = match ($method) {
                'get' => $http->get($url, $body),
                'post' => $http->post($url, $body),
                'put' => $http->put($url, $body),
                'patch' => $http->patch($url, $body),
                'delete' => $http->delete($url, $body),
                default => $http->send($request->method(), $url, ['body' => json_encode($body)]),
            };

            $status = $response->status();
            $data = $response->json();

            if ($data === null && $response->body() !== '') {
                $data = ['raw_response' => $response->body()];
            }

            return response()->json($data ?? [], $status);
        } catch (\Exception $e) {
            Log::error("Loom proxy failed [{$path}]: " . $e->getMessage());
            return response()->json([
                'ok' => false,
                'error' => 'NarrativeLoom communication failed: ' . $e->getMessage()
            ], 503);
        }
    }
}
