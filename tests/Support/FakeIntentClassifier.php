<?php

namespace Tests\Support;

use App\Enums\ChatbotIntent;
use App\Services\Chatbot\Contracts\IntentClassifier;
use App\Services\Chatbot\Intent\IntentClassification;

/** Clasificador falso para pruebas: sin red; devuelve una intención fija o una por frase, y cuenta las llamadas. */
final class FakeIntentClassifier implements IntentClassifier
{
    public int $calls = 0;

    /** @var list<ChatbotIntent> */
    public array $allowed = [];

    /** @param  array<string, ChatbotIntent>  $byMessage */
    public function __construct(
        private readonly ?ChatbotIntent $intent = null,
        private readonly string $outcome = 'OK',
        private readonly int $latencyMs = 1234,
        private readonly array $byMessage = [],
    ) {}

    public function classify(string $message, array $allowed): IntentClassification
    {
        $this->calls++;
        $this->allowed = $allowed;
        $intent = $this->byMessage[$message] ?? $this->intent;

        return new IntentClassification($this->outcome === 'OK' ? $intent : null, $this->outcome, $this->latencyMs);
    }
}
