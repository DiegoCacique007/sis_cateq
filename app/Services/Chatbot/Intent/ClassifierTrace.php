<?php

namespace App\Services\Chatbot\Intent;

use App\Enums\ChatbotIntent;

/**
 * Rastro del clasificador en modo assist, para la bitácora. Solo códigos.
 * outcome: USED (la respuesta salió del modelo) o FALLBACK_<motivo> (se usó la de las reglas).
 * ruleCode: lo que dieron las reglas (NO_MATCH o AMBIGUOUS_INTENT).
 */
final readonly class ClassifierTrace
{
    public function __construct(
        public string $outcome,
        public ?ChatbotIntent $intent,
        public int $latencyMs,
        public string $ruleCode,
    ) {}

    /** Latencia redondeada a 100 ms, como se registra. */
    public function roundedLatency(): int
    {
        return (int) (round($this->latencyMs / 100) * 100);
    }
}
