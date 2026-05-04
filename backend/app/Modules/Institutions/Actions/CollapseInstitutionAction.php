<?php

namespace App\Modules\Institutions\Actions;

use App\Modules\Narrative\Models\MythScar;
use App\Modules\Institutions\Contracts\InstitutionalRepositoryInterface;
use App\Modules\Institutions\Entities\InstitutionalEntity;

class CollapseInstitutionAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        private InstitutionalRepositoryInterface $institutionalRepository
    ) {}

    public function handle(InstitutionalEntity $entity, int $tick): void
    {
        $entity->collapse($tick);
        $this->institutionalRepository->save($entity);

        // Create Myth Scar
        $primaryZone = $this->getPrimaryZone($entity);

        MythScar::create([
            'universe_id' => $entity->universeId,
            'zone_id' => (string)$primaryZone,
            'name' => "Sự sụp đổ của " . $entity->name,
            'description' => "Định chế {$entity->name} đã tan rã, để lại một khoảng trống quyền lực và sẹo thần thoại.",
            'created_at_tick' => $tick,
            'severity' => 0.6,
        ]);
    }

    private function getPrimaryZone(InstitutionalEntity $entity): string
    {
        if (empty($entity->influenceMap)) {
            return '0';
        }
        
        $map = $entity->influenceMap;
        arsort($map);
        return (string) key($map);
    }
}

