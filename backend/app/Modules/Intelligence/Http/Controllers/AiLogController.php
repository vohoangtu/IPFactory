<?php

namespace App\Modules\Intelligence\Http\Controllers;

use App\Modules\Intelligence\Models\AiLog;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AiLogController extends Controller
{
    /**
     * List AI interaction logs with pagination and global search.
     */
    public function index(Request $request)
    {
        $query = AiLog::query();

        // Specific Filters
        if ($request->filled('feature')) {
            $query->where('feature', $request->feature);
        }
        if ($request->filled('driver')) {
            $query->where('driver', $request->driver);
        }
        if ($request->filled('model')) {
            $query->where('model', $request->model);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Global Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('feature', 'LIKE', "%{$search}%")
                  ->orWhere('driver', 'LIKE', "%{$search}%")
                  ->orWhere('model', 'LIKE', "%{$search}%")
                  ->orWhere('error_message', 'LIKE', "%{$search}%");
            });
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('limit', 15));

        return response()->json($logs);
    }

    /**
     * Get global AI interaction stats.
     */
    public function stats()
    {
        $total = AiLog::count();
        $successCount = AiLog::where('status', 'success')->count();
        
        $avgLatency = AiLog::where('status', 'success')->avg('latency_ms') ?: 0;
        $successRate = $total > 0 ? round(($successCount / $total) * 100, 1) : 0;

        $providerDistribution = AiLog::selectRaw('driver as name, count(*) as count')
            ->groupBy('driver')
            ->get();

        $modelDistribution = AiLog::selectRaw('model as name, count(*) as count')
            ->whereNotNull('model')
            ->groupBy('model')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        return response()->json([
            'total_requests' => $total,
            'success_rate' => $successRate,
            'avg_latency' => round($avgLatency),
            'providers' => $providerDistribution,
            'models' => $modelDistribution,
        ]);
    }

    /**
     * Get a specific log entry.
     */
    public function show($id)
    {
        $log = AiLog::findOrFail($id);
        return response()->json($log);
    }

    /**
     * Clear all logs (Admin function).
     */
    public function clear()
    {
        AiLog::truncate();
        return response()->json(['message' => 'Toàn bộ nhật ký AI đã được xóa.']);
    }
}
