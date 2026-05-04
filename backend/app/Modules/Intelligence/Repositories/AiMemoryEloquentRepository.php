<?php

namespace App\Modules\Intelligence\Repositories;

use App\Modules\Intelligence\Models\AiMemory as AiMemoryModel;
use App\Modules\Intelligence\Contracts\AiMemoryRepositoryInterface;
use App\Modules\Intelligence\Entities\AiMemoryEntity;

class AiMemoryEloquentRepository implements AiMemoryRepositoryInterface
{
    public function save(AiMemoryEntity $entity): void
    {
        AiMemoryModel::updateOrCreate(
            ['universe_id' => $entity->universeId, 'content_hash' => md5($entity->content)],
            [
                'scope' => $entity->scope,
                'category' => $entity->category,
                'keywords' => $entity->keywords,
                'content' => $entity->content,
                'embedding' => $entity->embedding,
                'importance' => $entity->importance,
                'expires_at' => $entity->expiresAt,
            ]
        );
    }

    public function search(int $universeId, string $query, int $limit = 10): array
    {
        // Simple keyword-based search for now
        return AiMemoryModel::where('universe_id', $universeId)
            ->where('content', 'LIKE', "%$query%")
            ->orderByDesc('importance')
            ->limit($limit)
            ->get()
            ->map(fn($model) => new AiMemoryEntity(
                universeId: $model->universe_id,
                scope: $model->scope,
                category: $model->category,
                content: $model->content,
                keywords: $model->keywords ?? [],
                embedding: $model->embedding,
                importance: (float) $model->importance,
                expiresAt: $model->expires_at ? new \DateTime($model->expires_at) : null
            ))
            ->toArray();
    }
}

