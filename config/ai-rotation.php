<?php

return [
    'cooldown_seconds' => (int) env('AI_ROTATION_COOLDOWN_SECONDS', 60),

    'providers' => [
        'groq' => [
            'class' => YourVendor\AIRotationManager\Providers\GroqProvider::class,
            'api_keys' => array_values(array_filter(array_map('trim', explode(',', (string) env('GROQ_API_KEYS', ''))))),
            'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
            'model' => env('GROQ_MODEL', 'llama-3.1-8b-instant'),
        ],
        'gemini' => [
            'class' => YourVendor\AIRotationManager\Providers\GeminiProvider::class,
            'api_keys' => array_values(array_filter(array_map('trim', explode(',', (string) env('GEMINI_API_KEYS', ''))))),
            'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
            'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
        ],
    ],

    'priority' => ['groq', 'gemini'],

    'default_options' => [
        'temperature' => 0.7,
        'max_tokens' => 1024,
    ],
];
