<?php

namespace App\Services\Chatbot\Intent;

use App\Enums\ChatbotIntent as I;
use App\Services\Chatbot\Contracts\IntentResolver;

final class RuleBasedIntentResolver implements IntentResolver
{
    public function __construct(
        private readonly MessageNormalizer $normalizer = new MessageNormalizer,
        private readonly IntentPatternCatalog $catalog = new IntentPatternCatalog,
    ) {}

    public function resolve(string $message): IntentResolution
    {
        if (! mb_check_encoding($message, 'UTF-8') || mb_strlen($message, 'UTF-8') > 2000) {
            return new IntentResolution(I::Unknown, 'INVALID_MESSAGE');
        }
        $text = $this->normalizer->normalize($message);
        if ($text === '') {
            return new IntentResolution(I::Unknown, 'EMPTY_MESSAGE');
        }
        $clauses = preg_split('/\s+\b(?:y|ademas|tambien)\b\s+|[;,.\n]+/u', $text, flags: PREG_SPLIT_NO_EMPTY);
        $matches = [];
        $candidates = [];
        $blocked = false;
        $prefix = '';
        foreach ($clauses as $clause) {
            $clause = trim($clause);
            // Elipsis acotada: «ver alumnos y calificaciones», «cómo administro periodos y usuarios».
            if (preg_match('/^(?:(?:un|una|el|la|los|las) )?(?:alumnos?|calificacion|calificaciones|evaluacion|evaluaciones|boletas?|tutor|tutores|comunidad|comunidades|periodos?|nivel|niveles|unidad|unidades|rubros?|usuarios?)$/u', $clause)) {
                $clause = $prefix.' '.$clause;
            }
            if (preg_match('/^(.*?\b(?:ver|consultar|muestrame|mostrar|administro|administrar|gestionar|gestiono|registrar|registro))\b/u', $clause, $parts)) {
                $prefix = $parts[1];
            } else {
                $prefix = '';
            }
            $guarded = false;
            foreach ($this->catalog->guards() as $id => $pattern) {
                if (preg_match($pattern, $clause)) {
                    $matches[] = $id;
                    $guarded = $blocked = true;
                }
            }
            if ($guarded) {
                continue;
            }
            $local = [];
            foreach ($this->catalog->patterns() as $id => $rule) {
                if (preg_match($rule['pattern'], $clause)) {
                    $local[$id] = $rule;
                }
            }
            $priority = $local ? max(array_column($local, 'priority')) : null;
            foreach ($local as $id => $rule) {
                if ($rule['priority'] === $priority) {
                    $matches[] = $id;
                    $candidates[$rule['intent']->value] = $rule['intent'];
                }
            }
        }
        $matches = array_values(array_unique($matches));
        $candidates = array_values($candidates);
        if (count($candidates) > 1) {
            return new IntentResolution(I::Unknown, 'AMBIGUOUS_INTENT', $matches, true, $candidates);
        }
        if ($blocked) {
            return new IntentResolution(I::Unknown, 'UNSUPPORTED_REQUEST', $matches, candidates: $candidates);
        }
        if (! $candidates) {
            return new IntentResolution(I::Unknown, 'NO_MATCH');
        }

        return new IntentResolution($candidates[0], 'INTENT_RESOLVED', $matches, candidates: $candidates);
    }
}
