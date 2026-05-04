<?php

namespace App\Modules\SocialGraph\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Modules\SocialGraph\Services\Neo4jSocialSyncer;
use App\Modules\Intelligence\Models\Actor;

class SyncSocialGraphJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $universeId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(Neo4jSocialSyncer $syncer): void
    {
        // Sync alive actors for this universe
        Actor::where('universe_id', $this->universeId)
            ->where('is_alive', true)
            ->chunk(100, function ($actors) use ($syncer) {
                $syncer->syncActors($actors);
            });
    }
}

