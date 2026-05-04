<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $manager = app(App\Modules\Intelligence\Services\AI\AiConfigManager::class);
    $config = config('ai');

    // Reset all settings from config/ai.php
    $manager->set('default', $config['default'], 'general', 'Driver AI mặc định');

    foreach ($config['features'] ?? [] as $f => $d) {
        $manager->set("features.$f", $d, 'feature', "Hệ thống mapping cho $f");
    }

    foreach ($config['drivers'] ?? [] as $dr => $data) {
        $manager->set("drivers.$dr", $data, 'provider', "Cấu hình cho driver $dr", true);
    }

    echo "Restored successfully\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
