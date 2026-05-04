<?php

namespace App\Modules\Simulation\Providers;

use App\Modules\Intelligence\Models\AiSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class SimulationConfigServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Chỉ chạy nếu bảng ai_settings tồn tại (tránh lỗi khi chạy migration lần đầu)
        if (! $this->app->runningInConsole() && ! $this->app->runningUnitTests()) {
            $this->loadDynamicConfig();
        } else {
            // Nếu chạy console (migration/seed), chỉ load nếu bảng đã tồn tại
            try {
                if (Schema::hasTable('ai_settings')) {
                    $this->loadDynamicConfig();
                }
            } catch (\Throwable) {
                // Ignore errors during early bootstrap
            }
        }
    }

    /**
     * Nạp cấu hình từ Database và ghi đè vào Laravel config.
     */
    private function loadDynamicConfig(): void
    {
        $settings = Cache::remember('worldos_dynamic_settings', 3600, function () {
            return AiSetting::whereIn('group', ['physics', 'simulation', 'psychology', 'entropy', 'general'])
                ->get(['key', 'value', 'group']);
        });

        foreach ($settings as $setting) {
            $value = $setting->value;
            
            // Một số key trong DB có thể cần tiền tố 'worldos.'
            // Chúng ta giả định key trong DB là key cấp dưới, ví dụ 'chaos.dampening_stability_factor'
            // sẽ được map vào 'worldos.chaos.dampening_stability_factor'
            
            config(["worldos.{$setting->key}" => $value]);
        }
    }
}
