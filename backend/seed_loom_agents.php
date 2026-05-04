<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$config = [
    "chief_editor" => ["tier" => "pro", "provider" => "openrouter", "model" => "openai/gpt-4o"],
    "historian" => ["tier" => "mini", "provider" => "openrouter", "model" => "google/gemini-flash-1.5"],
    "psychologist" => ["tier" => "pro", "provider" => "openrouter", "model" => "openai/gpt-4o"],
    "director" => ["tier" => "pro", "provider" => "openrouter", "model" => "openai/gpt-4o"],
    "wordsmith" => ["tier" => "pro", "provider" => "openrouter", "model" => "openai/gpt-4o"],
    "critic" => ["tier" => "mini", "provider" => "openrouter", "model" => "google/gemini-flash-1.5"],
    "archivist" => ["tier" => "mini", "provider" => "openrouter", "model" => "google/gemini-flash-1.5"],
    "mythologist" => ["tier" => "pro", "provider" => "openrouter", "model" => "openai/gpt-4o"],
    "news_anchor" => ["tier" => "mini", "provider" => "openrouter", "model" => "google/gemini-flash-1.5"],
    "vfx_director" => ["tier" => "mini", "provider" => "openrouter", "model" => "google/gemini-flash-1.5"],
    "failover" => ["provider" => "local", "model" => "qwen3.5-9b-uncensored-hauhaucs-aggressive"]
];

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$count = 0;
foreach ($config as $agentName => $agentConfig) {
    $key = "loom_agents." . $agentName;
    $value = json_encode($agentConfig);
    \App\Modules\Intelligence\Models\AiSetting::updateOrCreate(
        ["key" => $key],
        [
            "value" => $value,
            "group" => "loom_agents",
            "description" => "Cấu hình AI cho Loom agent: " . $agentName,
            "is_secret" => false
        ]
    );
    $count++;
}

echo "Đã nhập {$count} cấu hình Loom agents thành công.\n";
