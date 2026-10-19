<?php

namespace App\Services\Chatbot;

use App\Enums\UserRole;
use App\Models\ChatbotEvent;
use App\Services\Chatbot\Agents\AgentResponse;
use App\Services\Chatbot\Intent\IntentResolution;
use App\Services\Chatbot\Presentation\ChatbotResponseFormatter;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Registra un evento por respuesta del chatbot con datos ya proyectados: rol, intención, códigos,
 * reglas que bloquearon, si hubo selección y, solo para NO_MATCH, la forma enmascarada del mensaje.
 * Nunca recibe el mensaje en bruto, el usuario ni los ids de recursos. Un fallo no afecta la respuesta.
 */
final class ChatbotEventRecorder
{
    /** Patrones del resolvedor que bloquean una solicitud. */
    private const BLOCKING_PATTERNS = ['NEGATED-REQUEST', 'UNSUPPORTED-ATTENDANCE', 'UNSUPPORTED-EVALUATION-WRITE'];

    public function record(?UserRole $role, ?IntentResolution $resolution, AgentResponse $response, bool $withSelection, ?string $messageShape): void
    {
        try {
            $guards = $resolution ? array_values(array_intersect($resolution->matchedPatterns, self::BLOCKING_PATTERNS)) : [];
            // insert() sin conversión de fechas: se guarda solo el día (Y-m-d), nunca la hora.
            ChatbotEvent::query()->insert([
                'occurred_on' => today()->toDateString(),
                'role' => $role?->value,
                'intent' => $resolution?->intent->value,
                'resolution' => $resolution?->code,
                'guard' => $guards ? implode(',', $guards) : null,
                'status' => ChatbotResponseFormatter::status($response->status),
                'code' => $response->code,
                'with_selection' => $withSelection,
                'message_shape' => $resolution?->code === 'NO_MATCH' ? $messageShape : null,
            ]);
        } catch (Throwable $error) {
            // Igual que chatbot.failure: sin mensaje, SQL, bindings ni traza.
            Log::warning('chatbot.event_failed', ['exception_class' => get_class($error)]);
        }
    }
}
