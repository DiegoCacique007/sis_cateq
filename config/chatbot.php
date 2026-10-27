
<?php

return [
    'classifier' => [
        'mode' => env('CHATBOT_CLASSIFIER_MODE', 'off'),
        'driver' => env('CHATBOT_CLASSIFIER_DRIVER', 'none'),
        'timeout' => (int) env('CHATBOT_CLASSIFIER_TIMEOUT', 30),

        'ollama' => [
            'url' => env('CHATBOT_OLLAMA_URL', 'http://127.0.0.1:11434'),
            'model' => env('CHATBOT_OLLAMA_MODEL', 'hf.co/Mungert/Qwen3-4B-Instruct-2507-GGUF:Q4_K_M'),
            'keep_alive' => env('CHATBOT_OLLAMA_KEEP_ALIVE', '30m')
        ]
    ]
];
