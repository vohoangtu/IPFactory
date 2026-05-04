<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Exceptions;

/**
 * Thrown when writing simulation state to the database fails.
 *
 * The caller should treat this as a signal that the entire tick's
 * persistence was rolled back and no partial writes were committed.
 */
class StateWriteException extends \RuntimeException
{
    public function __construct(
        string $message = 'Failed to persist simulation state.',
        int $code = 0,
        ?\Throwable $previous = null,
        public readonly ?int $universeId = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
