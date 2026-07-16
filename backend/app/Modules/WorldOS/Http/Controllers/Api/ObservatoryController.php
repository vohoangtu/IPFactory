<?php

declare(strict_types=1);

namespace App\Modules\WorldOS\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\WorldOS\Actions\GetActorPsycheAction;
use App\Modules\WorldOS\Actions\GetObservatoryFeedAction;
use App\Modules\WorldOS\Actions\GetUniverseCivilizationAction;
use App\Modules\WorldOS\Actions\GetUniverseWorldAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ObservatoryController extends Controller
{
    public function __construct(
        private readonly GetObservatoryFeedAction $getObservatoryFeedAction,
        private readonly GetActorPsycheAction $getActorPsycheAction,
        private readonly GetUniverseCivilizationAction $getUniverseCivilizationAction,
        private readonly GetUniverseWorldAction $getUniverseWorldAction
    ) {
    }

    public function feed(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'after_tick' => ['sometimes', 'integer', 'min:0'],
            'before_tick' => ['sometimes', 'integer', 'min:0'],
            'types' => ['sometimes', 'string', 'max:500'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:200'],
        ]);

        $types = isset($validated['types'])
            ? array_values(array_filter(array_map('trim', explode(',', $validated['types']))))
            : null;

        return response()->json($this->getObservatoryFeedAction->handle($id, [
            'after_tick' => $validated['after_tick'] ?? null,
            'before_tick' => $validated['before_tick'] ?? null,
            'types' => $types !== [] ? $types : null,
            'limit' => $validated['limit'] ?? null,
        ]));
    }

    public function actorPsyche(int $actorId): JsonResponse
    {
        return response()->json($this->getActorPsycheAction->handle($actorId));
    }

    public function civilization(int $id): JsonResponse
    {
        return response()->json($this->getUniverseCivilizationAction->handle($id));
    }

    public function world(int $id): JsonResponse
    {
        return response()->json($this->getUniverseWorldAction->handle($id));
    }
}
