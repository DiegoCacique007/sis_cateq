<?php

namespace App\Services\Chatbot;

use App\Enums\UserRole;
use App\Models\ChatbotEvent;
use App\Services\Chatbot\Agents\AgentResponse;
use App\Services\Chatbot\Intent\IntentDescriptions;
use App\Services\Chatbot\Intent\IntentResolution;
use App\Services\Chatbot\Intent\ShadowClassificationTracker;
use App\Services\Chatbot\Presentation\ChatbotResponseFormatter;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Registra un evento por respuesta del chatbot con datos ya proyectados: rol, intención, códigos,
 * reglas que bloquearon, si hubo selección y, solo para NO_MATCH, la forma enmascarada del mensaje.
 * En modo assist añade el resultado del clasificador (solo códigos); en modo sombra lo completa
 * ShadowClassification después de la respuesta, sobre este mismo evento.
 * Nunca recibe el mensaje en bruto, el usuario ni los ids de recursos. Un fallo no afecta la respuesta.
 */
final class ChatbotEventRecorder
{
    /** Patrones del resolvedor que bloquean una solicitud. */
    private const BLOCKING_PATTERNS = ['NEGATED-REQUEST', 'UNSUPPORTED-ATTENDANCE', 'UNSUPPORTED-EVALUATION-WRITE'];

    public function __construct(private readonly ShadowClassificationTracker $tracker = new ShadowClassificationTracker) {}

    public function record(?UserRole $role, ?IntentResolution $resolution, AgentResponse $response, bool $withSelection, ?string $messageShape): void
    {
        try {
            $guards = $resolution ? array_values(array_intersect($resolution->matchedPatterns, self::BLOCKING_PATTERNS)) : [];
            // resolution guarda lo que dieron las reglas, aunque la respuesta viniera del modelo.
            $ruleCode = $resolution?->classifier?->ruleCode ?? $resolution?->code;
            // insert() sin conversión de fechas: se guarda solo el día (Y-m-d), nunca la hora.
            $event = [
                'occurred_on' => today()->toDateString(),
                'role' => $role?->value,
                'intent' => $resolution?->intent->value,
                'resolution' => $ruleCode,
                'guard' => $guards ? implode(',', $guards) : null,
                'status' => ChatbotResponseFormatter::status($response->status),
                'code' => $response->code,
                'with_selection' => $withSelection,
                'message_shape' => $ruleCode === 'NO_MATCH' ? $messageShape : null,
            ];
            // Las columnas del clasificador solo se escriben cuando hay datos: sin modelo, el evento es el de siempre.
            if ($trace = $resolution?->classifier) {
                $event += [
                    'classifier_intent' => $trace->intent?->value,
                    'classifier_outcome' => $trace->outcome,
                    'classifier_latency_ms' => $trace->roundedLatency(),
                    'prompt_version' => IntentDescriptions::VERSION,
                ];
            }
            $this->tracker->recordEvent(ChatbotEvent::query()->insertGetId($event));
        } catch (Throwable $error) {
            // Igual que chatbot.failure: sin mensaje, SQL, bindings ni traza.
            Log::warning('chatbot.event_failed', ['exception_class' => get_class($error)]);
        }
    }
}
