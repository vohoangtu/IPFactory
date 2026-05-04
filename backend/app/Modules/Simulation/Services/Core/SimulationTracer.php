<?php

namespace App\Modules\Simulation\Services\Core;

use Illuminate\Support\Facades\Log;
use Closure;

/**
 * Doc §31: Simulation tracing (Jaeger/OpenTelemetry).
 * When worldos.observability.tracing_enabled is true, delegates span creation
 * to the keepsuit/laravel-opentelemetry tracer.
 */
final class SimulationTracer
{
    /**
     * Run the given callable inside a named span. When tracing is disabled, runs the callable directly.
     *
     * @template T
     * @param  callable(): T  $callback
     * @return T
     */
    public static function span(string $name, callable $callback): mixed
    {
        if (! config('worldos.observability.tracing_enabled', false)) {
            return $callback();
        }

        if (class_exists('Keepsuit\LaravelOpenTelemetry\Facades\Tracer')) {
            $tracer = app('Keepsuit\LaravelOpenTelemetry\Facades\Tracer');
            $closure = $callback instanceof Closure ? $callback : Closure::fromCallable($callback);
            
            // The measure method executes the closure inside an active span scope
            return $tracer->newSpan($name)->measure($closure);
        }

        // Fallback if package is not installed but tracing is enabled
        $start = microtime(true);
        Log::debug("SimulationTracer: fallback_span_start [{$name}]");
        try {
            $result = $callback();
            Log::debug("SimulationTracer: fallback_span_end [{$name}]", ['duration_ms' => round((microtime(true) - $start) * 1000, 2)]);
            return $result;
        } catch (\Throwable $e) {
            Log::debug("SimulationTracer: fallback_span_error [{$name}]", ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}

