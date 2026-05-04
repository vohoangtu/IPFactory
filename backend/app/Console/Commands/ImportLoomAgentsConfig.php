<?php

namespace App\Console\Commands;

use App\Modules\Intelligence\Models\AiSetting;
use App\Modules\Intelligence\Services\AI\AiConfigManager;
use Illuminate\Console\Command;

class ImportLoomAgentsConfig extends Command
{
    protected $signature = 'ai:import-loom-agents {--json= : JSON content directly}';
    protected $description = 'Import Loom agents configuration from agent_routing.json into database';

    public function __construct(
        protected AiConfigManager $configManager
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $jsonContent = $this->option('json');

        if ($jsonContent) {
            $config = json_decode($jsonContent, true);
        } else {
            // Fallback: thử đọc file từ các path khác nhau
            $paths = [
                base_path('../../narrative-loom/configs/agent_routing.json'),
                base_path('../narrative-loom/configs/agent_routing.json'),
                base_path('narrative-loom/configs/agent_routing.json'),
            ];

            $jsonPath = null;
            foreach ($paths as $path) {
                if (file_exists($path)) {
                    $jsonPath = $path;
                    break;
                }
            }

            if (!$jsonPath) {
                $this->error("File agent_routing.json không tồn tại. Hãy dùng --json parameter để truyền nội dung trực tiếp.");
                return 1;
            }

            $config = json_decode(file_get_contents($jsonPath), true);
        }

        if (!is_array($config)) {
            $this->error('Không thể đọc JSON');
            return 1;
        }

        $count = 0;
        foreach ($config as $agentName => $agentConfig) {
            $key = "loom_agents.{$agentName}";
            $value = json_encode($agentConfig);

            AiSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'group' => 'loom_agents',
                    'description' => "Cấu hình AI cho Loom agent: {$agentName}",
                    'is_secret' => false,
                ]
            );

            $count++;
        }

        $this->configManager->syncToCache();

        $this->info("Đã nhập {$count} cấu hình Loom agents từ file JSON thành công.");
        return 0;
    }
}
