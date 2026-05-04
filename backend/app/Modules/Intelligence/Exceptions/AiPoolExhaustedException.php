<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Exceptions;

use RuntimeException;

/**
 * Thrown when no usable AI key can be resolved from ai_key_pool for the
 * requested feature/provider/tier combination.
 *
 * Callers SHOULD catch this exception and degrade gracefully (e.g. return null,
 * fall back to rule-based behaviour) rather than letting the simulation crash.
 */
class AiPoolExhaustedException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $feature = 'general',
        public readonly ?string $provider = null,
        public readonly string $tier = 'any',
        public readonly ?string $modelGroup = null,
        public readonly ?string $model = null,
    ) {
        parent::__construct($message);
    }

    public static function forFeature(
        string $feature,
        ?string $provider = null,
        string $tier = 'any',
        ?string $modelGroup = null,
        ?string $model = null,
    ): self {
        $message = sprintf(
            'AI key pool exhausted: feature=%s, provider=%s, tier=%s, model_group=%s, model=%s',
            $feature,
            $provider ?? 'any',
            $tier,
            $modelGroup ?? 'any',
            $model ?? 'any',
        );

        return new self($message, $feature, $provider, $tier, $modelGroup, $model);
    }
}
