<?php

namespace App\Modules\WorldOS\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Narrative\Contracts\ArtifactRepositoryInterface;
use App\Modules\Narrative\Contracts\ChronicleRepositoryInterface;
use App\Modules\Narrative\Contracts\MythScarRepositoryInterface;
use App\Modules\WorldOS\Http\Resources\ChronicleResource;
use App\Modules\WorldOS\Http\Resources\MythScarResource;
use Illuminate\Http\JsonResponse;

class NarrativeController extends Controller
{
    public function __construct(
        private ChronicleRepositoryInterface $chronicleRepo,
        private MythScarRepositoryInterface $mythScarRepo,
        private ArtifactRepositoryInterface $artifactRepo
    ) {}

    public function chronicles(int $universeId): JsonResponse
    {
        $chronicles = $this->chronicleRepo->findByUniverse($universeId);

        return ChronicleResource::collection(collect($chronicles))->response();
    }

    public function show(Chronicle $chronicle): ChronicleResource
    {
        return new ChronicleResource($chronicle);
    }

    public function mythScars(int $universeId): JsonResponse
    {
        $scars = $this->mythScarRepo->findByUniverse($universeId);

        return MythScarResource::collection(collect($scars))->response();
    }

    public function artifacts(int $universeId): JsonResponse
    {
        return response()->json([
            'data' => $this->artifactRepo->findByUniverse($universeId),
        ]);
    }
}
