<?php

namespace App\Services\Chatbot\Intent;

use App\Enums\ChatbotIntent;

final readonly class IntentResolution
{
    /**
     * @param  list<string>  $matchedPatterns  Identificadores del catálogo; sin texto personal.
     * @param  list<ChatbotIntent>  $candidates
     */
    public function __construct(
        public ChatbotIntent $intent,
        public string $code,
        public array $matchedPatterns = [],
        public bool $ambiguous = false,
        public array $candidates = [],
    ) {}
}
