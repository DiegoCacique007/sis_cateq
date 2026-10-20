<?php

namespace App\Services\Chatbot\Intent;

use App\Enums\ChatbotIntent as I;
use App\Services\Chatbot\Contracts\IntentClassifier;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Clasificación en modo sombra, ejecutada después de enviar la respuesta (dispatchAfterResponse).
 * No implementa ShouldQueue: se ejecuta en el mismo proceso, sin serializar el mensaje ni pasar por
 * la cola o failed_jobs. Solo registra códigos: nunca el mensaje, el prompt ni la respuesta del modelo.
 */
final class ShadowClassification
{
    use Dispatchable;

    /** Application::terminate() no vacía sus callbacks: si se llamara dos veces, no se clasifica dos veces. */
    private bool $handled = false;

    public function __construct(
        private readonly string $message,
        private readonly string $ruleIntent,
        private readonly string $ruleCode,
    ) {}

    public function handle(IntentClassifier $classifier, IntentDescriptions $descriptions, WriteRequestFilter $writes): void
    {
        if ($this->handled) {
            return;
        }
        $this->handled = true;

        try {
            $result = $classifier->classify($this->message, $descriptions->offered());
            $shadow = $result->intent ? $writes->filter($result->intent, $this->message) : null;

            Log::info('chatbot.shadow', [
                'rule_intent' => $this->ruleIntent,
                'rule_code' => $this->ruleCode,
                'shadow_intent' => $shadow?->value,
                'shadow_outcome' => $result->outcome === 'OK' ? $this->compare($shadow) : $result->outcome,
                'shadow_latency_ms' => (int) (round($result->latencyMs / 100) * 100),
                'prompt_version' => IntentDescriptions::VERSION,
            ]);
        } catch (Throwable $error) {
            Log::warning('chatbot.shadow_failed', ['exception_class' => get_class($error)]);
        }
    }

    /** AGREE: misma decisión que las reglas. DISAGREE: otra. RESCUE: las reglas no resolvieron y el modelo sí. */
    private function compare(I $shadow): string
    {
        if ($this->ruleCode === 'INTENT_RESOLVED') {
            return $shadow->value === $this->ruleIntent ? 'AGREE' : 'DISAGREE';
        }

        return $shadow === I::Unknown ? 'AGREE' : 'RESCUE';
    }
}
