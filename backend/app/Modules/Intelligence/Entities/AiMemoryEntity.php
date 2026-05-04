<?php

namespace App\Modules\Intelligence\Entities;

class AiMemoryEntity
{
    public function __construct(
        public readonly int $universeId,
        public readonly string $scope,
        public readonly string $category,
        public string $content,
        public array $keywords = [],
        public ?array $embedding = null,
        public float $importance = 1.0,
        public ?\DateTime $expiresAt = null
    ) {}

    /**
     * Kiểm tra xem ký ức có còn giá trị sử dụng không.
     */
    public function isExpired(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt < new \DateTime();
    }

    /**
     * Suy giảm độ quan trọng theo thời gian (Decay).
     */
    public function decay(float $factor = 0.95): void
    {
        $this->importance *= $factor;
    }
}
