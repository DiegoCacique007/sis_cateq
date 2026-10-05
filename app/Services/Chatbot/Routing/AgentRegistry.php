<?php

namespace App\Services\Chatbot\Routing;

use App\Enums\ChatbotIntent as I;
use App\Enums\ChatbotRuleResult as S;
use App\Services\Chatbot\Agents\AdministrationAgent;
use App\Services\Chatbot\Agents\AgentGate;
use App\Services\Chatbot\Agents\AgentRequest;
use App\Services\Chatbot\Agents\EscalationAgent;
use App\Services\Chatbot\Agents\EvaluationsAgent;
use App\Services\Chatbot\Agents\GroupsAgent;
use App\Services\Chatbot\Agents\HelpAgent;
use App\Services\Chatbot\Agents\StudentsAgent;
use App\Services\Chatbot\Contracts\Agent;

final class AgentRegistry
{
    public function __construct(
        private readonly GroupsAgent $groups,
        private readonly StudentsAgent $students,
        private readonly EvaluationsAgent $evaluations,
        private readonly HelpAgent $help,
        private readonly AdministrationAgent $administration,
        private readonly EscalationAgent $escalation,
    ) {}

    public function resolve(AgentRequest $request): Agent
    {
        if ($request->decision->result !== S::Allowed && ! AgentGate::requiresAssignmentSelection($request, $request->decision)) {
            return $this->escalation;
        }

        return match ($request->facts->intent) {
            I::ViewMyGroups, I::ViewAttendanceList => $this->groups,
            I::ViewGroupStudents => $this->students,
            I::ViewEvaluations => $this->evaluations,
            I::HelpSystem, I::ViewBoleta => $this->help,
            I::HowToRegisterStudent, I::HowToRegisterTutor, I::HowToRegisterInscription,
            I::HowToAssignGroup, I::HowToManageCommunities, I::HowToManagePeriods,
            I::HowToManageLevels, I::HowToManageUnits, I::HowToManageRubrics,
            I::HowToManageUsers, I::HowToRecordEvaluations => $this->administration,
            I::ModifyStudent, I::ModifyAdministrativeData, I::Unknown => $this->escalation,
        };
    }
}
