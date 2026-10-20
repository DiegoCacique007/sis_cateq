<?php

namespace App\Services\Chatbot\Intent;

use App\Enums\ChatbotIntent;

/**
 * Resultado de un clasificador. outcome: OK, EMPTY, INVALID_JSON, OUT_OF_LIST, TIMEOUT, HTTP_ERROR o DISABLED.
 * Solo con OK hay intención; sin texto del mensaje ni de la respuesta del modelo.
 */
final readonly class IntentClassification
{
    public function __construct(
        public ?ChatbotIntent $intent,
        public string $outcome,
        public int $latencyMs = 0,
    ) {}

    public static function failed(string $outcome, int $latencyMs = 0): self
    {
        return new self(null, $outcome, $latencyMs);
    }
}
