<?php

namespace App\Modules\Intelligence\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Intelligence\Models\AiProviderModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AiProviderModelsController extends Controller
{
    public function index(): JsonResponse
    {
        $models = AiProviderModel::all();
        return response()->json($models);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => 'required|string',
            'model_name' => 'required|string|unique:ai_provider_models,model_name',
            'display_name' => 'required|string',
            'is_active' => 'boolean',
            'metadata' => 'nullable|array',
        ]);

        $model = AiProviderModel::create($validated);
        return response()->json($model, 201);
    }

    public function show(int $id): JsonResponse
    {
        $model = AiProviderModel::findOrFail($id);
        return response()->json($model);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $model = AiProviderModel::findOrFail($id);

        $validated = $request->validate([
            'provider' => 'string',
            'model_name' => 'string|unique:ai_provider_models,model_name,' . $id,
            'display_name' => 'string',
            'is_active' => 'boolean',
            'metadata' => 'nullable|array',
        ]);

        $model->update($validated);
        return response()->json($model);
    }

    public function destroy(int $id): JsonResponse
    {
        $model = AiProviderModel::findOrFail($id);
        $model->delete();
        return response()->json(null, 204);
    }

    public function import(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'data' => 'required|array',
            'data.version' => 'required|string',
            'data.provider_models' => 'required|array',
        ]);

        $imported = 0;
        foreach ($validated['data']['provider_models'] as $item) {
            AiProviderModel::updateOrCreate(
                [
                    'provider' => $item['provider'],
                    'model_name' => $item['model_name'],
                ],
                [
                    'display_name' => $item['display_name'],
                    'is_active' => $item['is_active'] ?? true,
                    'metadata' => $item['metadata'] ?? null,
                ]
            );
            $imported++;
        }

        return response()->json([
            'message' => 'Imported successfully',
            'count' => $imported,
        ]);
    }

    public function export(): JsonResponse
    {
        $models = AiProviderModel::all();
        return response()->json([
            'version' => '1.0',
            'provider_models' => $models,
        ]);
    }
}
