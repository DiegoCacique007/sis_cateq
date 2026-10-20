<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Clasificador de intenciones (modelo de lenguaje)
    |--------------------------------------------------------------------------
    |
    | mode:
    |   off    (por defecto) solo reglas; no se contacta a ningún proveedor.
    |   shadow las respuestas siguen saliendo de las reglas; el modelo clasifica
    |          después de responder y solo se registra la comparación.
    | Cualquier otro valor se trata como off.
    |
    | driver: none | ollama. Un proveedor remoto enviaría el texto del usuario a
    | un tercero: requiere una decisión explícita (ver docs/despliegue.md).
    |
    */

    'classifier' => [
        'mode' => env('CHATBOT_CLASSIFIER_MODE', 'off'),
        'driver' => env('CHATBOT_CLASSIFIER_DRIVER', 'none'),
        'timeout' => (int) env('CHATBOT_CLASSIFIER_TIMEOUT', 8),

        'ollama' => [
            'url' => env('CHATBOT_OLLAMA_URL', 'http://localhost:11434'),
            'model' => env('CHATBOT_OLLAMA_MODEL', 'hf.co/Mungert/Qwen3-4B-Instruct-2507-GGUF:Q4_K_M'),
            'keep_alive' => env('CHATBOT_OLLAMA_KEEP_ALIVE', '10m'),
        ],
    ],

];
