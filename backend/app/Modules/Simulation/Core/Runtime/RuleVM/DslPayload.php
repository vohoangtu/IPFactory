<?php

namespace App\Modules\Simulation\Core\Runtime\RuleVM;

/**
 * DslPayload – An opaque container for DSL code.
 * PHP treats this as a "black box" payload for the Rust engine.
 */
class DslPayload
{
    private string $rawContent;
    private array $metadata;

    public function __construct(string $rawContent, array $metadata = [])
    {
        $this->rawContent = $rawContent;
        $this->metadata = $metadata;
    }

    public function getRawContent(): string
    {
        return $this->rawContent;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function isEmpty(): bool
    {
        return empty(trim($this->rawContent));
    }

    public function __toString(): string
    {
        return $this->rawContent;
    }
}
