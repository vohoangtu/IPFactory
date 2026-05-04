<?php

namespace App\Modules\Intelligence\Http\Controllers;

use App\Modules\Intelligence\Models\AgentConfig;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AgentConfigController extends Controller
{
    public function show()
    {
        return AgentConfig::first() ?? response()->json(['message' => 'Không tìm thấy cấu hình'], 404);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'agent_name' => 'required|string',
            'personality' => 'required|string',
            'creativity' => 'required|integer|min:0|max:100',
            'themes' => 'array',
            'model_type' => 'required|string',
            'local_endpoint' => 'nullable|string',
            'model_name' => 'nullable|string',
            'api_key' => 'nullable|string',
        ]);

        $config = AgentConfig::first();
        if ($config) {
            $config->update($validated);
        } else {
            $config = AgentConfig::create($validated);
        }

        return response()->json($config);
    }
}

