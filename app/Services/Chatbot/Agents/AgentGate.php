<?php

namespace App\Services\Chatbot\Agents;

use App\Enums\ChatbotIntent as I;
use App\Enums\ChatbotRuleResult as S;
use App\Services\Chatbot\Rules\RuleEngine;
use App\Services\Chatbot\Rules\RuleResult;

/** Revalida mediante el motor; no define permisos propios. */
final class AgentGate
{
    public function __construct(private readonly RuleEngine $engine) {}

    public static function requiresAssignmentSelection(AgentRequest $request, RuleResult $decision): bool
    {
        return $decision->result === S::MissingContext && $decision->code === 'MISSING_RESOURCE'
            && $request->facts->assignmentId === null
            && in_array($request->facts->intent, [I::ViewGroupStudents, I::ViewAttendanceList], true);
    }

    public function reject(AgentRequest $request, bool $supported, bool $allowSelection = false): ?AgentResponse
    {
        if (! $supported) {
            return new AgentResponse('UNSUPPORTED', 'INTENT_NOT_SUPPORTED', 'chatbot.function_unavailable');
        }
        $decision = $this->engine->evaluate($request->facts);
        if ($decision->result === S::Allowed || ($allowSelection && self::requiresAssignmentSelection($request, $decision))) {
            return null;
        }

        return EscalationAgent::response($decision);
    }
}
