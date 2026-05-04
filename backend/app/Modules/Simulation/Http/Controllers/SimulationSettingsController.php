<?php

namespace App\Modules\Simulation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Intelligence\Models\AiSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class SimulationSettingsController extends Controller
{
    /**
     * Lấy danh sách cấu hình mô phỏng được nhóm theo category.
     */
    public function index(): JsonResponse
    {
        $settings = AiSetting::whereIn('group', ['physics', 'simulation', 'psychology', 'entropy', 'general'])
            ->get()
            ->groupBy('group');

        // Đảm bảo trả về cấu trúc mặc định nếu database trống
        $categories = [
            'general' => $settings->get('general', []),
            'physics' => $settings->get('physics', []),
            'simulation' => $settings->get('simulation', []),
            'psychology' => $settings->get('psychology', []),
            'entropy' => $settings->get('entropy', []),
        ];

        return response()->json($categories);
    }

    /**
     * Cập nhật một hoặc nhiều cấu hình.
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'present',
            'settings.*.group' => 'required|string',
        ]);

        foreach ($request->input('settings') as $item) {
            AiSetting::updateOrCreate(
                ['key' => $item['key']],
                [
                    'value' => $item['value'],
                    'group' => $item['group'],
                    'description' => $item['description'] ?? null,
                ]
            );
        }

        // Clear cache và reload config (giả định có ServiceProvider xử lý việc này)
        Cache::forget('worldos_dynamic_settings');
        
        return response()->json(['message' => 'Simulation settings updated successfully.']);
    }

    /**
     * Khôi phục cài đặt gốc từ config/worldos.php.
     */
    public function reset(Request $request): JsonResponse
    {
        $group = $request->input('group');

        $query = AiSetting::whereIn('group', ['physics', 'simulation', 'psychology', 'entropy', 'general']);
        
        if ($group) {
            $query->where('group', $group);
        }

        $query->delete();
        Cache::forget('worldos_dynamic_settings');

        return response()->json(['message' => 'Settings reset to system defaults.']);
    }
}
