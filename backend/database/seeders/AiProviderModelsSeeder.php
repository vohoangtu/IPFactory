<?php

namespace Database\Seeders;

use App\Modules\Intelligence\Models\AiProviderModel;
use Illuminate\Database\Seeder;

class AiProviderModelsSeeder extends Seeder
{
    public function run(): void
    {
        $models = [
            // OpenAI
            [
                'provider' => 'openai',
                'model_name' => 'gpt-4o',
                'display_name' => 'GPT-4o',
                'is_active' => true,
                'metadata' => ['tier' => 'pro', 'context_length' => 128000],
            ],
            [
                'provider' => 'openai',
                'model_name' => 'gpt-4-turbo',
                'display_name' => 'GPT-4 Turbo',
                'is_active' => true,
                'metadata' => ['tier' => 'pro', 'context_length' => 128000],
            ],
            [
                'provider' => 'openai',
                'model_name' => 'gpt-3.5-turbo',
                'display_name' => 'GPT-3.5 Turbo',
                'is_active' => true,
                'metadata' => ['tier' => 'mini', 'context_length' => 16385],
            ],

            // Anthropic
            [
                'provider' => 'anthropic',
                'model_name' => 'claude-3-opus-20240229',
                'display_name' => 'Claude 3 Opus',
                'is_active' => true,
                'metadata' => ['tier' => 'pro', 'context_length' => 200000],
            ],
            [
                'provider' => 'anthropic',
                'model_name' => 'claude-3-sonnet-20240229',
                'display_name' => 'Claude 3 Sonnet',
                'is_active' => true,
                'metadata' => ['tier' => 'pro', 'context_length' => 200000],
            ],
            [
                'provider' => 'anthropic',
                'model_name' => 'claude-3-haiku-20240307',
                'display_name' => 'Claude 3 Haiku',
                'is_active' => true,
                'metadata' => ['tier' => 'mini', 'context_length' => 200000],
            ],

            // Google
            [
                'provider' => 'google',
                'model_name' => 'gemini-1.5-pro-latest',
                'display_name' => 'Gemini 1.5 Pro',
                'is_active' => true,
                'metadata' => ['tier' => 'pro', 'context_length' => 1000000],
            ],
            [
                'provider' => 'google',
                'model_name' => 'gemini-1.5-flash-latest',
                'display_name' => 'Gemini 1.5 Flash',
                'is_active' => true,
                'metadata' => ['tier' => 'mini', 'context_length' => 1000000],
            ],

            // Local
            [
                'provider' => 'local',
                'model_name' => 'qwen3.5-9b-uncensored-hauhaucs-aggressive',
                'display_name' => 'Qwen 3.5 9B',
                'is_active' => true,
                'metadata' => ['tier' => 'free', 'context_length' => 32768],
            ],
            [
                'provider' => 'local',
                'model_name' => 'llama-3-8b',
                'display_name' => 'Llama 3 8B',
                'is_active' => true,
                'metadata' => ['tier' => 'free', 'context_length' => 8192],
            ],

            // OpenRouter
            [
                'provider' => 'openrouter',
                'model_name' => 'google/gemini-flash-1.5',
                'display_name' => 'Gemini Flash 1.5 (OpenRouter)',
                'is_active' => true,
                'metadata' => ['tier' => 'mini', 'context_length' => 1000000],
            ],
            [
                'provider' => 'openrouter',
                'model_name' => 'anthropic/claude-3-haiku',
                'display_name' => 'Claude 3 Haiku (OpenRouter)',
                'is_active' => true,
                'metadata' => ['tier' => 'mini', 'context_length' => 200000],
            ],

            // Zai
            [
                'provider' => 'zai',
                'model_name' => 'GLM-4.5-Flash',
                'display_name' => 'GLM-4.5 Flash',
                'is_active' => true,
                'metadata' => ['tier' => 'mini', 'context_length' => 128000],
            ],
        ];

        foreach ($models as $model) {
            AiProviderModel::updateOrCreate(
                [
                    'provider' => $model['provider'],
                    'model_name' => $model['model_name'],
                ],
                $model
            );
        }
    }
}
