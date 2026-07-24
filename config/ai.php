<?php

return [
    'default' => env('AI_PROVIDER', 'openai'),

    'providers' => [
        'openai' => [
            'driver' => 'openai',
            'key' => env('OPENAI_API_KEY'),
            'model' => 'text-embedding-3-small',
            'dimensions' => 1024,
        ],
        'openrouter' => [
            'driver' => 'openai-compatible',
            'key' => env('OPENROUTER_API_KEY'),
            'base_url' => 'https://openrouter.ai/api/v1',
            'model' => 'nvidia/nemotron-3-embed-1b:free',
            'dimensions' => 1024,
        ],
    ],
];
