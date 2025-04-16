<?php

return [

    'default' => env('PROMPT_DRIVER', 'chatgpt'),

    'drivers' => [
        'chatgpt' => [
            'api_key' => env('OPENAI_API_KEY'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1/'),
        ],

        'ollama' => [
            'host' => env('OLLAMA_HOST', 'http://localhost:11434'),
            'model' => env('OLLAMA_MODEL', 'llama3'),
        ],

        'deepseek' => [
            'api_key' => env('DEEPSEEK_API_KEY'),
            'endpoint' => env('DEEPSEEK_ENDPOINT', 'https://api.deepseek.com/v1/'),
        ],
    ],
];