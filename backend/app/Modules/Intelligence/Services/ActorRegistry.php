<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\World\Models\World;
use App\Modules\Intelligence\Entities\Contracts\ActorArchetypeInterface;

class ActorRegistry
{
    /** @var ActorArchetypeInterface[] */
    protected array $archetypes = [];

    /**
     * Auto-discovery via tagged service container.
     *
     * @param iterable<ActorArchetypeInterface> $archetypes
     */
    public function __construct(iterable $archetypes)
    {
        $this->archetypes = $archetypes instanceof \Traversable
            ? iterator_to_array($archetypes)
            : (array) $archetypes;
    }

    /**
     * Lọc danh sách archetypes phù hợp với thế giới hiện tại.
     *
     * @return ActorArchetypeInterface[]
     */
    public function getEligibleArchetypes(World $world): array
    {
        return array_values(
            array_filter($this->archetypes, fn(ActorArchetypeInterface $a) => $a->isEligible($world))
        );
    }

    /**
     * Trả về toàn bộ archetypes đã đăng ký (không lọc).
     *
     * @return ActorArchetypeInterface[]
     */
    public function all(): array
    {
        return $this->archetypes;
    }
}

