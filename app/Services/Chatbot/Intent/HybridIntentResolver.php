<?php

namespace App\Services\Chatbot\Intent;

use App\Services\Chatbot\Contracts\IntentResolver;

/**
 * Reglas primero, siempre. Modos (config chatbot.classifier.mode):
 * - off: solo reglas (por defecto).
 * - shadow: la respuesta sale de las reglas; el modelo clasifica después de responder y solo se registra.
 * Cualquier otro valor se trata como off (assist llegará en una tarea posterior).
 */
final class HybridIntentResolver implements IntentResolver
{
    /** Nunca se envían al modelo: bloqueados por una guarda, vacíos o inválidos. */
    private const NOT_CLASSIFIED = ['UNSUPPORTED_REQUEST', 'EMPTY_MESSAGE', 'INVALID_MESSAGE'];

    public function __construct(
        private readonly RuleBasedIntentResolver $rules,
        private readonly string $mode = 'off',
        private readonly ShadowClassificationTracker $tracker = new ShadowClassificationTracker,
        private readonly IntentPatternCatalog $catalog = new IntentPatternCatalog,
    ) {}

    public function resolve(string $message): IntentResolution
    {
        $resolution = $this->rules->resolve($message);

        if ($this->mode === 'shadow' && $this->classifiable($resolution)) {
            ShadowClassification::dispatchAfterResponse($message, $resolution->intent->value, $resolution->code);
            $this->tracker->markPending();
        }

        return $resolution;
    }

    private function classifiable(IntentResolution $resolution): bool
    {
        return ! in_array($resolution->code, self::NOT_CLASSIFIED, true)
            && array_intersect($resolution->matchedPatterns, array_keys($this->catalog->guards())) === [];
    }
}
