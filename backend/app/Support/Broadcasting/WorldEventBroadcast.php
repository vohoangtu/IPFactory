<?php

declare(strict_types=1);

namespace App\Support\Broadcasting;

interface WorldEventBroadcast
{
    public function envelope(): WorldEventEnvelope;
}
