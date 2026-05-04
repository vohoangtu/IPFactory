<?php

namespace App\Modules\World\ValueObjects;

/**
 * TradeOffer đại diện cho một thông điệp trao đổi hàng hóa.
 * Bao gồm danh sách vật phẩm cho đi và yêu cầu nhận lại.
 */
class TradeOffer
{
    public function __construct(
        public readonly string $actorId,
        public readonly array $giveItems,
        public readonly array $requestItems,
        public readonly int $createdAtTick
    ) {
    }

    public function toArray(): array
    {
        return [
            'actor_id' => $this->actorId,
            'give_items' => $this->giveItems,
            'request_items' => $this->requestItems,
            'created_at_tick' => $this->createdAtTick,
        ];
    }
}
