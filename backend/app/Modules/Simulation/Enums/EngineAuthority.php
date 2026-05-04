<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Enums;

/**
 * Engine Authority Classification.
 *
 * Defines the relationship between a PHP engine and the Rust gRPC engine:
 * - SUPPLEMENT: PHP computes something Rust does not. Always runs.
 * - OVERLAP: Rust already computes the same fields. Skipped when rust_authoritative=true.
 * - BRIDGE: PHP wrapper that delegates to Rust. Skipped when rust_authoritative=true.
 */
enum EngineAuthority: string
{
    case SUPPLEMENT = 'supplement';
    case OVERLAP = 'overlap';
    case BRIDGE = 'bridge';
}
