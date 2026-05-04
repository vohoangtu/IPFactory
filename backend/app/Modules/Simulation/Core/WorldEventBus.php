<?php

namespace App\Modules\Simulation\Core;

use App\Modules\Simulation\Core\Contracts\WorldEventBusBackendInterface;
use App\Modules\Simulation\Core\Contracts\WorldEventBusInterface;
use App\Modules\Simulation\Core\Events\WorldEvent;

/**
 * Event Bus for WorldEvent (doc §4, §16). Delegates persist+dispatch to backend; notifies in-process subscribers.
 */
final class WorldEventBus implements WorldEventBusInterface
{
    /** @var array<string, callable[]> type => [callable, ...] */
    private array $subscribers = [];

    public function __construct(
        private readonly WorldEventBusBackendInterface $backend,
    ) {
    }

    public function publish(WorldEvent $event): void
    {
        $this->backend->publish($event);
        $this->notifySubscribers($event);
    }

    /**
     * Subscribe to events of a given type. Callable receives WorldEvent.
     */
    public function subscribe(string $type, callable $handler): void
    {
        if (! isset($this->subscribers[$type])) {
            $this->subscribers[$type] = [];
        }
        $this->subscribers[$type][] = $handler;
    }

    private function notifySubscribers(WorldEvent $event): void
    {
        $handlers = $this->subscribers[$event->type] ?? [];
        $allHandlers = $this->subscribers['*'] ?? [];
        foreach (array_merge($allHandlers, $handlers) as $handler) {
            $handler($event);
        }
    }
}
