<?php

namespace App\Services\Chatbot\Intent;

use App\Enums\ChatbotIntent;

final readonly class IntentResolution
{
    /**
     * @param  list<string>  $matchedPatterns  Identificadores del catálogo; sin texto personal.
     * @param  list<ChatbotIntent>  $candidates
     * @param  ?ClassifierTrace  $classifier  Solo en modo assist, cuando se consultó al modelo.
     */
    public function __construct(
        public ChatbotIntent $intent,
        public string $code,
        public array $matchedPatterns = [],
        public bool $ambiguous = false,
        public array $candidates = [],
        public ?ClassifierTrace $classifier = null,
    ) {}
}
