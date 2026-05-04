<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Marker interface for Action classes.
 *
 * All Action classes MUST implement this interface and provide a public
 * execute() method. The signature is intentionally not enforced here so
 * that each Action can declare its own strongly-typed parameters and
 * return types.
 */
interface ActionInterface
{
}
