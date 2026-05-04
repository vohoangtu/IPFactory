<?php

namespace App\Modules\SocialGraph\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\SocialGraph\Services\Neo4jSocialSyncer;
use App\Modules\Intelligence\Models\Actor;
use App\Modules\World\Models\Universe;

class Neo4jSyncCommand extends Command
{
    protected $signature = 'worldos:graph-sync {universe_id?}';
    protected $description = 'Sync actor social relations to Neo4j graph database.';

    public function handle(Neo4jSocialSyncer $syncer): int
    {
        $uId = $this->argument('universe_id');
        
        if ($uId) {
            $universes = Universe::where('id', $uId)->get();
        } else {
            $universes = Universe::all();
        }

        foreach ($universes as $universe) {
            $this->info("Syncing Social Graph for Universe #{$universe->id}...");
            
            Actor::where('universe_id', $universe->id)
                ->where('is_alive', true)
                ->chunk(100, function ($actors) use ($syncer) {
                    $syncer->syncActors($actors);
                    $this->output->write('.');
                });
            
            $this->newLine();
        }

        $this->info('Social Graph sync completed.');
        return self::SUCCESS;
    }
}

