<?php

namespace App\Services\Chatbot\Agents;

use App\Enums\ChatbotIntent as I;
use App\Services\Authorization\CatequesisAccess;
use App\Services\Chatbot\Contracts\Agent;
use App\Services\Chatbot\Rules\RuleCatalog;

final class HelpAgent implements Agent
{
    public function __construct(
        private readonly AgentGate $gate,
        private readonly CatequesisAccess $access,
        private readonly RuleCatalog $catalog,
    ) {}

    public function supports(I $intent): bool
    {
        return in_array($intent, [I::HelpSystem, I::ViewBoleta], true);
    }

    public function handle(AgentRequest $request): AgentResponse
    {
        if ($rejection = $this->gate->reject($request, $this->supports($request->facts->intent))) {
            return $rejection;
        }
        if ($request->facts->intent === I::ViewBoleta) {
            return new AgentResponse('OK', 'BOLETA_MODULE_GUIDANCE', 'chatbot.boleta_in_existing_module',
                ['steps' => ['Abre Boletas y selecciona los filtros y el alumno para generar su boleta en el módulo.']],
                [['type' => 'navigate', 'route' => $request->facts->context->role->value.'.boletas.index']]);
        }
        $intents = [];
        foreach ([I::ViewMyGroups, I::ViewGroupStudents, I::ViewAttendanceList, I::ViewEvaluations, I::ViewBoleta, ...AdministrationAgent::intents()] as $intent) {
            if ($this->access->can($request->facts->context, $this->catalog->capability($intent))->isAllowed()) {
                $intents[] = $intent->value;
            }
        }

        return new AgentResponse('OK', 'HELP_READY', 'chatbot.help_ready', ['availableIntents' => $intents]);
    }
}
