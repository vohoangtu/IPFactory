<?php

namespace App\Modules\Institutions\Actions;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Intelligence\Contracts\ActorRepositoryInterface;
use App\Modules\Institutions\Actions\SpawnSupremeEntityAction;

class AscendHeroAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        private ActorRepositoryInterface $actorRepository,
        private SpawnSupremeEntityAction $spawnSupremeEntityAction
    ) {}

    public function handle(Universe $universe, UniverseSnapshot $snapshot): void
    {
        // Find heroic actors with influence > 85 (Logic from SupremeEntityEngine)
        $candidates = $this->actorRepository->findActiveByUniverse($universe->id);
        
        foreach ($candidates as $actor) {
             // We work with ActorEntity here if using repository, else update repository to filter
             if (($actor->metrics['influence'] ?? 0) > 85.0) {
                 $traits = $actor->traits ?? [];
                 if (($traits[1] ?? 0) > 0.95 || ($traits[8] ?? 0) > 0.95) {
                     if (rand(1, 100) <= 20) {
                         $this->ascend($universe, $snapshot, $actor);
                     }
                 }
             }
        }
    }

    private function ascend(Universe $universe, UniverseSnapshot $snapshot, $actor): void
    {
        $traits = $actor->traits ?? [];
        
        $this->spawnSupremeEntityAction->handle($universe, (int)$snapshot->tick, [
            'name' => "Thần Vương {$actor->name}",
            'entity_type' => 'ascended_hero',
            'domain' => 'Di sản Nhân quả: ' . ($actor->archetype ?? 'Hero'),
            'description' => "Vị anh hùng huyền thoại {$actor->name} đã vượt qua giới hạn của xác thịt, thăng hoa thành bất tử.",
            'power_level' => 0.7,
            'alignment' => [
                'spirituality' => ($traits[4] ?? 0.5),
                'hardtech' => ($traits[7] ?? 0.5),
                'entropy' => 0.2, 
                'energy_level' => 0.9
            ]
        ], $actor->id);

        // Mark actor as "ascended" (legacy logic was died but transended)
        $actor->applyAscension((int)$snapshot->tick);
        $this->actorRepository->save($actor);
    }
}

