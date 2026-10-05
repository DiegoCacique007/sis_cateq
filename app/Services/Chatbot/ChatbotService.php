<?php

namespace App\Services\Chatbot;

use App\Services\Authorization\AccessContextResolver;
use App\Services\Chatbot\Agents\AgentRequest;
use App\Services\Chatbot\Agents\AgentResponse;
use App\Services\Chatbot\Contracts\IntentResolver;
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
    ) {}

    public function handle(Request $request, string $message, ?int $assignmentId = null, ?int $inscriptionId = null, ?int $studentId = null, ?int $evaluationId = null): AgentResponse
    {
        try {
            $context = $this->contexts->resolve($request);
            $resolution = $this->intents->resolve($message);
            if ($resolution->ambiguous) {
                return new AgentResponse('AMBIGUOUS', 'AMBIGUOUS_INTENT', 'chatbot.clarify_intent');
            }
            $facts = new ChatbotFacts($context, $resolution->intent, $assignmentId, $inscriptionId, $studentId, $evaluationId);
            $agentRequest = AgentRequest::evaluate($facts, $this->engine);

            return $this->registry->resolve($agentRequest)->handle($agentRequest);
        } catch (AuthorizationException) {
            return new AgentResponse('DENIED', 'UNAUTHORIZED_CONTEXT', 'chatbot.access_denied');
        } catch (Throwable $error) {
            // Nunca registrar mensaje, SQL, bindings, stack trace ni la excepción completa.
            Log::error('chatbot.failure', ['code' => 'CHATBOT_ERROR', 'exception_class' => get_class($error)]);

            return new AgentResponse('ERROR', 'CHATBOT_ERROR', 'chatbot.error');
        }
    }
}
