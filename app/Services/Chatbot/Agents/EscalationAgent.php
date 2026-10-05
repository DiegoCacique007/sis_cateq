<?php

namespace App\Services\Chatbot\Agents;

use App\Enums\ChatbotIntent;
use App\Enums\ChatbotRuleResult as S;
use App\Services\Chatbot\Contracts\Agent;
use App\Services\Chatbot\Rules\RuleEngine;
use App\Services\Chatbot\Rules\RuleResult;

final class EscalationAgent implements Agent
{
    public function __construct(private readonly RuleEngine $engine) {}

    public function supports(ChatbotIntent $intent): bool
    {
        return true; // El routing de esta responsabilidad depende del resultado, no del dominio.
    }

    public function handle(AgentRequest $request): AgentResponse
    {
        return self::response($this->engine->evaluate($request->facts));
    }

    public static function response(RuleResult $decision): AgentResponse
    {
        $key = match ($decision->result) {
            S::Denied => 'chatbot.access_denied',
            S::Escalate => 'chatbot.contact_responsible',
            S::MissingContext => 'chatbot.context_required',
            S::Ambiguous => 'chatbot.inconsistent_assignment',
            default => 'chatbot.function_unavailable',
        };
        $data = [];
        if ($decision->result === S::MissingContext) {
            $data['required'] = match ($decision->code) {
                'MISSING_PERIOD' => ['activePeriodId'],
                'MISSING_COMMUNITY' => ['communityId'],
                'MISSING_RESOURCE' => array_values(array_intersect($decision->context['required'] ?? [], ['assignmentId', 'inscriptionId', 'studentId', 'evaluationId'])),
                default => [],
            };
        }
        $actions = [];
        if (in_array($decision->recommendedResponsible, ['secretaria', 'system_human'], true)) {
            $actions[] = ['type' => 'contact', 'responsible' => $decision->recommendedResponsible];
        }
        if ($decision->result === S::Ambiguous) {
            $actions[] = ['type' => 'review_assignment_data'];
            $actions[] = ['type' => 'request_clarification', 'reason' => 'assignment_data'];
        }

        return new AgentResponse(
            $decision->result === S::Allowed ? 'UNSUPPORTED' : $decision->result->value,
            $decision->result === S::Allowed ? 'AGENT_NOT_AVAILABLE' : $decision->code,
            $key, $data, $actions,
        );
    }
}
