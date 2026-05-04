<?php

namespace App\Modules\Psychology\ValueObjects;

/**
 * Impulse – a single internal drive generated from a Meaning.
 *
 * Multiple impulses can coexist and conflict (Freudian id-ego-superego metaphor).
 * Types: desire (want to approach), fear (want to avoid), duty (obligation),
 *        identity (self-concept defense).
 */
final class Impulse
{
    public const TYPE_DESIRE   = 'desire';
    public const TYPE_FEAR     = 'fear';
    public const TYPE_DUTY     = 'duty';
    public const TYPE_IDENTITY = 'identity';

    public const ACTION_APPROACH  = 'approach';
    public const ACTION_AVOID     = 'avoid';
    public const ACTION_ATTACK    = 'attack';
    public const ACTION_WITHDRAW  = 'withdraw';
    public const ACTION_DEFEND    = 'defend';
    public const ACTION_COOPERATE = 'cooperate';

    public function __construct(
        public readonly string $type,
        public readonly string $action,
        public float           $intensity, // mutable (can be suppressed by ConflictResolver)
        public readonly float  $urgency,
        /** @var string[] */
        public readonly array  $tags = [],
    ) {}

    public function isOpposedTo(self $other): bool
    {
        $opposites = [
            self::ACTION_APPROACH  => [self::ACTION_AVOID, self::ACTION_WITHDRAW],
            self::ACTION_AVOID     => [self::ACTION_APPROACH, self::ACTION_ATTACK],
            self::ACTION_ATTACK    => [self::ACTION_AVOID, self::ACTION_COOPERATE],
            self::ACTION_DEFEND    => [self::ACTION_ATTACK],
            self::ACTION_COOPERATE => [self::ACTION_ATTACK, self::ACTION_AVOID],
            self::ACTION_WITHDRAW  => [self::ACTION_APPROACH],
        ];

        return in_array($other->action, $opposites[$this->action] ?? [], true);
    }

    /**
     * Suppress this impulse (not eliminated – suppressed impulses can leak).
     */
    public function suppress(float $factor = 0.5): void
    {
        $this->intensity = max(0.0, $this->intensity * $factor);
    }

    public function toArray(): array
    {
        return [
            'type'      => $this->type,
            'action'    => $this->action,
            'intensity' => $this->intensity,
            'urgency'   => $this->urgency,
            'tags'      => $this->tags,
        ];
    }
}
