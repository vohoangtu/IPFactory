<?php

namespace App\Modules\Intelligence\Listeners;

use App\Modules\Simulation\Core\Events\ActorDiedEvent;
use Illuminate\Support\Facades\Log;

class ActorDiedEventListener
{
    public function handle(ActorDiedEvent $event): void
    {
        $actorId = $event->payload['actor_id'];
        Log::info("Intelligence: Cleaning up active decision loops for deceased actor {$actorId}.");
        
        // TODO: Lưu trữ "Di chúc" hoặc "Ký ức cuối cùng" vào AI Memory
    }
}
