<?php

namespace App\Modules\Intelligence\Services\AI;

use App\Modules\Intelligence\Models\AiSetting;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class AiConfigManager
{
    protected string $cachePath;
    protected array $config = [];

    public function __construct()
    {
        $this->cachePath = storage_path('app/ai_config_cache.json');
        $this->load();
    }

    /**
     * Get a config value by key (e.g., 'features.narrative' or 'drivers.zai.api_key').
     */
    public function get(string $key, $default = null)
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * Set a config value and sync to DB and file cache.
     */
    public function set(string $key, $value, ?string $group = null, ?string $description = null, bool $isSecret = false): void
    {
        AiSetting::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : $value,
                'group' => $group ?? $this->inferGroup($key),
                'description' => $description,
                'is_secret' => $isSecret,
            ]
        );

        $this->syncToCache();
    }

    /**
     * Sync database records to the local file cache.
     */
    public function syncToCache(): void
    {
        try {
            $settings = AiSetting::all();
            $data = [];

            foreach ($settings as $setting) {
                $value = $setting->value;
                // Attempt to decode JSON if it looks like JSON
                if (is_string($value) && (str_starts_with($value, '{') || str_starts_with($value, '['))) {
                    $decoded = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $value = $decoded;
                    }
                }
                data_set($data, $setting->key, $value);
            }

            File::put($this->cachePath, json_encode($data, JSON_PRETTY_PRINT));
            $this->config = $data;
        } catch (\Exception $e) {
            Log::error("Failed to sync AI config to cache: " . $e->getMessage());
        }
    }

    /**
     * Clear the cache.
     */
    public function clearCache(): void
    {
        if (File::exists($this->cachePath)) {
            File::delete($this->cachePath);
        }
        $this->config = [];
    }

    /**
     * Load config from cache or DB.
     */
    protected function load(): void
    {
        if (File::exists($this->cachePath)) {
            $json = File::get($this->cachePath);
            $this->config = json_decode($json, true) ?? [];
        } else {
            $this->syncToCache();
        }
    }

    protected function inferGroup(string $key): string
    {
        if (str_starts_with($key, 'features.')) return 'feature';
        if (str_starts_with($key, 'drivers.')) return 'provider';
        return 'general';
    }
}
