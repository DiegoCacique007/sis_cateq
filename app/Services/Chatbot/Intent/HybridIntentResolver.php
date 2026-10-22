<?php

namespace App\Services\Chatbot\Intent;

use App\Enums\ChatbotIntent as I;
use App\Services\Chatbot\Contracts\IntentClassifier;
use App\Services\Chatbot\Contracts\IntentResolver;

/**
 * Reglas primero, siempre. Modos (config chatbot.classifier.mode):
 * - off: solo reglas (por defecto).
 * - shadow: la respuesta sale de las reglas; el modelo clasifica después de responder y solo se registra.
 * - assist: si las reglas dan NO_MATCH o AMBIGUOUS_INTENT, se consulta al modelo en la misma petición
 *   (el usuario espera). Su intención pasa por el filtro de escritura y después por el motor de reglas y
 *   los agentes, que revalidan permisos. Ante cualquier fallo o respuesta descartada, quedan las reglas.
 * Cualquier otro valor se trata como off.
 */
final class HybridIntentResolver implements IntentResolver
{
    /** Nunca se envían al modelo: bloqueados por una guarda, vacíos o inválidos. */
    private const NOT_CLASSIFIED = ['UNSUPPORTED_REQUEST', 'EMPTY_MESSAGE', 'INVALID_MESSAGE'];

    /** En assist, el modelo solo interviene cuando las reglas no resolvieron. */
    private const ASSISTED = ['NO_MATCH', 'AMBIGUOUS_INTENT'];

    public function __construct(
        private readonly RuleBasedIntentResolver $rules,
        private readonly string $mode = 'off',
        private readonly ShadowClassificationTracker $tracker = new ShadowClassificationTracker,
        private readonly IntentPatternCatalog $catalog = new IntentPatternCatalog,
        private readonly ?IntentClassifier $classifier = null,
        private readonly IntentDescriptions $descriptions = new IntentDescriptions,
        private readonly WriteRequestFilter $writes = new WriteRequestFilter,
    ) {}

    public function resolve(string $message): IntentResolution
    {
        $resolution = $this->rules->resolve($message);
        if (! $this->classifiable($resolution)) {
            return $resolution;
        }

        return match ($this->mode) {
            'shadow' => $this->shadow($message, $resolution),
            'assist' => $this->assist($message, $resolution),
            default => $resolution,
        };
    }

    private function shadow(string $message, IntentResolution $resolution): IntentResolution
    {
        ShadowClassification::dispatchAfterResponse($message, $resolution->intent->value, $resolution->code);
        $this->tracker->markPending();

        return $resolution;
    }

    private function assist(string $message, IntentResolution $resolution): IntentResolution
    {
        if (! in_array($resolution->code, self::ASSISTED, true) || $this->classifier === null) {
            return $resolution;
        }
        // Con ambigüedad, solo entre los candidatos de las reglas (UNKNOWN = ninguno: quedan las reglas).
        $allowed = $resolution->ambiguous ? $this->candidates($resolution) : $this->descriptions->offered();
        if ($allowed === [I::Unknown]) {
            return $resolution;
        }

        $result = $this->classifier->classify($message, $allowed);
        $fallback = fn (string $reason, ?I $intent = null) => new IntentResolution(
            $resolution->intent, $resolution->code, $resolution->matchedPatterns, $resolution->ambiguous, $resolution->candidates,
            new ClassifierTrace('FALLBACK_'.$reason, $intent, $result->latencyMs, $resolution->code),
        );

        if ($result->outcome !== 'OK' || $result->intent === null) {
            return $fallback($result->outcome === 'OK' ? 'EMPTY' : $result->outcome);
        }
        // Segunda comprobación, aunque el proveedor ya valide la lista.
        if (! in_array($result->intent, $allowed, true)) {
            return $fallback('OUT_OF_LIST');
        }
        if ($result->intent === I::Unknown) {
            return $fallback('UNKNOWN', I::Unknown);
        }
        if ($this->writes->filter($result->intent, $message) === I::Unknown) {
            return $fallback('WRITE', $result->intent);
        }

        return new IntentResolution($result->intent, 'MODEL_RESOLVED', candidates: [$result->intent],
            classifier: new ClassifierTrace('USED', $result->intent, $result->latencyMs, $resolution->code));
    }

    /** @return list<I> Candidatos de las reglas que se pueden ofrecer al modelo, más UNKNOWN. */
    private function candidates(IntentResolution $resolution): array
    {
        $offered = $this->descriptions->offered();
        $candidates = array_values(array_filter($resolution->candidates, fn (I $intent) => $intent !== I::Unknown && in_array($intent, $offered, true)));

        return [...$candidates, I::Unknown];
    }

    private function classifiable(IntentResolution $resolution): bool
    {
        return ! in_array($resolution->code, self::NOT_CLASSIFIED, true)
            && array_intersect($resolution->matchedPatterns, array_keys($this->catalog->guards())) === [];
    }
}
