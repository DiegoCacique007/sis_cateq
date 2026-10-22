<?php

namespace App\Services\Chatbot;

use App\Services\Authorization\AccessContext;
use App\Services\Authorization\AccessContextResolver;
use App\Services\Chatbot\Agents\AgentRequest;
use App\Services\Chatbot\Agents\AgentResponse;
use App\Services\Chatbot\Agents\EscalationAgent;
use App\Services\Chatbot\Contracts\IntentResolver;
use App\Services\Chatbot\Intent\IntentResolution;
use App\Services\Chatbot\Intent\MessageShaper;
use App\Services\Chatbot\Routing\AgentRegistry;
use App\Services\Chatbot\Rules\ChatbotFacts;
use App\Services\Chatbot\Rules\RuleEngine;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ChatbotService
{
    public function __construct(
        private readonly AccessContextResolver $contexts,
        private readonly IntentResolver $intents,
        private readonly RuleEngine $engine,
        private readonly AgentRegistry $registry,
        private readonly ChatbotEventRecorder $events,
        private readonly MessageShaper $shaper,
    ) {}

    public function handle(Request $request, string $message, ?int $assignmentId = null, ?int $inscriptionId = null, ?int $studentId = null, ?int $evaluationId = null): AgentResponse
    {
        $context = null;
        $resolution = null;
        try {
            $context = $this->contexts->resolve($request);
            $resolution = $this->intents->resolve($message);
            $response = $this->respond($context, $resolution, $assignmentId, $inscriptionId, $studentId, $evaluationId);
        } catch (AuthorizationException) {
            $response = new AgentResponse('DENIED', 'UNAUTHORIZED_CONTEXT', 'chatbot.access_denied');
        } catch (Throwable $error) {
            // Nunca registrar mensaje, SQL, bindings, stack trace ni la excepción completa.
            Log::error('chatbot.failure', ['code' => 'CHATBOT_ERROR', 'exception_class' => get_class($error)]);
            $response = new AgentResponse('ERROR', 'CHATBOT_ERROR', 'chatbot.error');
        }
        // Un único punto de registro para todas las salidas; la bitácora nunca recibe el mensaje en bruto.
        $this->events->record($context?->role, $resolution, $response,
            $assignmentId !== null || $inscriptionId !== null || $studentId !== null || $evaluationId !== null,
            ($resolution?->classifier?->ruleCode ?? $resolution?->code) === 'NO_MATCH' ? $this->shaper->shape($message) : null);

        return $response;
    }

    private function respond(AccessContext $context, IntentResolution $resolution, ?int $assignmentId, ?int $inscriptionId, ?int $studentId, ?int $evaluationId): AgentResponse
    {
        if ($resolution->ambiguous) {
            return new AgentResponse('AMBIGUOUS', 'AMBIGUOUS_INTENT', 'chatbot.clarify_intent');
        }
        // Bloqueo por una única guarda de escritura: explicar en lugar de «no disponible». Sin datos.
        $writeGuards = array_values(array_intersect($resolution->matchedPatterns, EscalationAgent::WRITE_GUARDS));
        if ($resolution->code === 'UNSUPPORTED_REQUEST' && count($writeGuards) === 1) {
            return EscalationAgent::blockedWrite($writeGuards[0], $context->role);
        }
        $facts = new ChatbotFacts($context, $resolution->intent, $assignmentId, $inscriptionId, $studentId, $evaluationId);
        $agentRequest = AgentRequest::evaluate($facts, $this->engine);

        return $this->registry->resolve($agentRequest)->handle($agentRequest);
    }
}
