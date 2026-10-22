<?php

namespace App\Services\Chatbot\Intent;

use App\Enums\ChatbotIntent as I;
use App\Models\ChatbotEvent;
use App\Services\Chatbot\Contracts\IntentClassifier;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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

    public function handle(IntentClassifier $classifier, IntentDescriptions $descriptions, WriteRequestFilter $writes,
        ShadowClassificationTracker $tracker, MessageShaper $shaper): void
    {
        if ($this->handled) {
            return;
        }
        $this->handled = true;

        try {
            $result = $classifier->classify($this->message, $descriptions->offered());
            $shadow = $result->intent ? $writes->filter($result->intent, $this->message) : null;
            $outcome = $result->outcome === 'OK' ? $this->compare($shadow) : $result->outcome;
            $latency = (int) (round($result->latencyMs / 100) * 100);

            Log::info('chatbot.shadow', [
                'rule_intent' => $this->ruleIntent,
                'rule_code' => $this->ruleCode,
                'shadow_intent' => $shadow?->value,
                'shadow_outcome' => $outcome,
                'shadow_latency_ms' => $latency,
                'prompt_version' => IntentDescriptions::VERSION,
            ]);
            $this->persist($tracker->eventId(), $shadow, $outcome, $latency,
                in_array($outcome, ['DISAGREE', 'RESCUE'], true) ? $shaper->shape($this->message) : null);
        } catch (Throwable $error) {
            Log::warning('chatbot.shadow_failed', ['exception_class' => get_class($error)]);
        }
    }

    /** Completa el evento de esta petición. Sin la migración del clasificador, solo queda el log. */
    private function persist(?int $eventId, ?I $shadow, string $outcome, int $latency, ?string $shape): void
    {
        if ($eventId === null || ! Schema::hasColumn('chatbot_events', 'classifier_outcome')) {
            return;
        }
        $changes = [
            'classifier_intent' => $shadow?->value,
            'classifier_outcome' => $outcome,
            'classifier_latency_ms' => $latency,
            'prompt_version' => IntentDescriptions::VERSION,
        ];
        // La forma (enmascarada, 30 días) solo en desacuerdos y rescates; NO_MATCH ya la trae.
        if ($shape !== null) {
            $changes['message_shape'] = $shape;
        }
        ChatbotEvent::query()->whereKey($eventId)->update($changes);
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
