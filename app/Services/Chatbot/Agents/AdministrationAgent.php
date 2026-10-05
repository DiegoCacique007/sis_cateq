<?php

namespace App\Services\Chatbot\Agents;

use App\Enums\ChatbotIntent as I;
use App\Services\Chatbot\Contracts\Agent;
use App\Services\Chatbot\Knowledge\SystemGuidance;

final class AdministrationAgent implements Agent
{
    public function __construct(private readonly AgentGate $gate, private readonly SystemGuidance $guidance) {}

    public function supports(I $intent): bool
    {
        return in_array($intent, self::intents(), true);
    }

    /** @return list<I> */
    public static function intents(): array
    {
        return [I::HowToRegisterStudent, I::HowToRegisterTutor, I::HowToRegisterInscription,
            I::HowToAssignGroup, I::HowToManageCommunities, I::HowToManagePeriods,
            I::HowToManageLevels, I::HowToManageUnits, I::HowToManageRubrics,
            I::HowToManageUsers, I::HowToRecordEvaluations];
    }

    public function handle(AgentRequest $request): AgentResponse
    {
        if ($rejection = $this->gate->reject($request, $this->supports($request->facts->intent))) {
            return $rejection;
        }
        $entry = $this->guidance->for($request->facts->intent, $request->facts->context->role);

        return new AgentResponse('OK', 'GUIDANCE_READY', 'chatbot.guidance_ready', ['steps' => $entry['steps']],
            [['type' => 'navigate', 'route' => $entry['route']]], ['execution' => false]);
    }
}
