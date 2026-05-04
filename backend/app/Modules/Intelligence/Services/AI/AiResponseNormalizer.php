<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Services\AI;

class AiResponseNormalizer
{
    public function normalizeFeatureProfile(mixed $config): array
    {
        if (is_string($config) && trim($config) !== '') {
            return ['driver' => trim($config)];
        }

        if (! is_array($config)) {
            return [];
        }

        $profile = [];

        foreach (['driver', 'provider', 'model', 'model_group', 'tier'] as $key) {
            if (isset($config[$key]) && is_string($config[$key]) && trim($config[$key]) !== '') {
                $profile[$key] = trim($config[$key]);
            }
        }

        foreach (['max_tokens', 'timeout'] as $key) {
            if (isset($config[$key]) && is_numeric($config[$key])) {
                $profile[$key] = (int) $config[$key];
            }
        }

        foreach (['temperature', 'top_p'] as $key) {
            if (isset($config[$key]) && is_numeric($config[$key])) {
                $profile[$key] = (float) $config[$key];
            }
        }

        return $profile;
    }

    public function extractDriverOverrides(array $featureProfile): array
    {
        $overrides = [];

        foreach (['url', 'key', 'model'] as $key) {
            if (isset($featureProfile[$key]) && $featureProfile[$key] !== '') {
                $overrides[$key] = $featureProfile[$key];
            }
        }

        return $overrides;
    }

    public function extractDefaultOptions(array $featureProfile): array
    {
        $defaults = [];

        foreach (['max_tokens', 'temperature', 'timeout', 'top_p'] as $key) {
            if (isset($featureProfile[$key]) && $featureProfile[$key] !== '') {
                $defaults[$key] = $featureProfile[$key];
            }
        }

        return $defaults;
    }

    public function defaultUrlForProvider(string $provider): ?string
    {
        return match ($provider) {
            'zai' => 'https://api.z.ai/api/paas/v4/chat/completions',
            'openai' => 'https://api.openai.com/v1/chat/completions',
            'gemini' => 'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions',
            'openrouter' => 'https://openrouter.ai/api/v1/chat/completions',
            'local' => 'http://host.docker.internal:11434/v1/chat/completions',
            'qwen' => 'https://dashscope.aliyuncs.com/compatible-mode/v1',
            default => null,
        };
    }

    public function defaultModelForProvider(string $provider): ?string
    {
        return match ($provider) {
            'zai' => 'GLM-4.5-Flash',
            'openai' => 'gpt-4o',
            'gemini' => 'gemini-1.5-flash',
            'openrouter' => null, // Never hardcode a specific model — let caller decide
            'local' => 'qwen2.5:7b',
            'qwen' => 'qwen-max',
            default => null,
        };
    }
}
