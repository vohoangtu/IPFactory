<?php

return [

    /*
     |--------------------------------------------------------------------------
     | Default AI Provider
     |--------------------------------------------------------------------------
     |
     | Used as a fallback provider filter when a feature does not declare one.
     | Actual API keys are ALWAYS resolved from the `ai_key_pool` table —
     | static driver credentials have been removed (pool-only mode).
     |
     */

    'default' => env('AI_DRIVER', 'zai'),

    /*
     |--------------------------------------------------------------------------
     | AI Driver Metadata
     |--------------------------------------------------------------------------
     |
     | Only metadata (base URL, default model) is kept here. API keys MUST be
     | imported into `ai_key_pool`. Values declared per-pool-entry (metadata
     | column) override these defaults.
     |
     */

    'drivers' => [

        'zai' => [
            'url' => env('NARRATIVE_LLM_URL', 'https://api.z.ai/api/paas/v4/chat/completions'),
            'model' => env('NARRATIVE_LLM_MODEL', 'GLM-4.5-Flash'),
        ],

        'openai' => [
            'url' => env('OPENAI_URL', 'https://api.openai.com/v1/chat/completions'),
            'model' => env('OPENAI_MODEL', 'gpt-4o'),
        ],

        'gemini' => [
            'url' => env('GEMINI_URL', 'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions'),
            'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
        ],

        'local' => [
            'url' => env('LOCAL_LLM_URL', 'http://host.docker.internal:11434/v1/chat/completions'),
            'model' => env('LOCAL_LLM_MODEL', 'qwen2.5:7b'),
        ],

        'openrouter' => [
            'url' => env('OPENROUTER_URL', 'https://openrouter.ai/api/v1/chat/completions'),
            'model' => env('OPENROUTER_MODEL', 'google/gemini-2.0-flash-001'),
        ],

        'qwen' => [
            'url' => env('QWEN_LLM_URL', 'https://dashscope.aliyuncs.com/compatible-mode/v1'),
            'model' => env('QWEN_LLM_MODEL', 'qwen-max'),
        ],

    ],

    /*
     |--------------------------------------------------------------------------
     | AI Feature Mapping
     |--------------------------------------------------------------------------
     |
     | Maps simulation features to a preferred provider. The provider name is
     | used to filter `ai_key_pool` entries when resolving a key.
     |
     */

    'features' => [
        'analytical' => env('AI_FEATURE_ANALYTICAL', 'zai'),
        'narrative'  => env('AI_FEATURE_NARRATIVE', 'zai'),
        'lab'        => env('AI_FEATURE_LAB', 'zai'),
        'decision'   => env('AI_FEATURE_DECISION', 'zai'),
        'general'    => env('AI_FEATURE_GENERAL', 'zai'),
    ],

];
