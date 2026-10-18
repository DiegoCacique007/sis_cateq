<?php

namespace App\Services\Chatbot\Agents;

use App\Enums\CatequesisCapability as C;
use App\Enums\ChatbotIntent;
use App\Enums\ChatbotRuleResult as S;
use App\Enums\UserRole;
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
        return self::response($this->engine->evaluate($request->facts), $request->facts->context->role);
    }

    /** El rol solo ajusta el texto (no derivar al responsable a sí mismo); nunca concede acceso. */
    public static function response(RuleResult $decision, ?UserRole $role = null): AgentResponse
    {
        $selfResponsible = $role !== null && $decision->recommendedResponsible === $role->value;
        $key = match (true) {
            // Solo llega quien posee MANAGE_STUDENTS: la capacidad se valida antes de EXECUTION_UNSUPPORTED.
            $decision->result === S::Unsupported && $decision->code === 'EXECUTION_UNSUPPORTED'
                && $decision->capability === C::ManageStudents => 'chatbot.edit_in_module',
            $decision->result === S::Escalate && $selfResponsible => 'chatbot.change_in_module',
            $decision->result === S::Denied && $decision->code === 'ROLE_NOT_ALLOWED' => match ($decision->capability) {
                C::ViewBoletas => 'chatbot.boleta_by_secretary',
                C::ViewAttendanceList => 'chatbot.attendance_by_catechist',
                C::ManageEvaluations => 'chatbot.grades_by_recorders',
                default => 'chatbot.access_denied',
            },
            default => match ($decision->result) {
                S::Denied => 'chatbot.access_denied',
                S::Escalate => 'chatbot.contact_responsible',
                S::MissingContext => 'chatbot.context_required',
                S::Ambiguous => 'chatbot.inconsistent_assignment',
                default => 'chatbot.function_unavailable',
            },
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
        if ($key === 'chatbot.edit_in_module') {
            $actions[] = ['type' => 'navigate', 'route' => 'secretaria.alumnos.index'];
        }
        if (! $selfResponsible && in_array($decision->recommendedResponsible, ['secretaria', 'system_human'], true)) {
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
