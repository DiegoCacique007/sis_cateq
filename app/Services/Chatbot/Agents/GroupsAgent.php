<?php

namespace App\Services\Chatbot\Agents;

use App\Enums\ChatbotIntent as I;
use App\Services\Chatbot\Contracts\Agent;

final class GroupsAgent implements Agent
{
    public function __construct(private readonly AgentGate $gate, private readonly AssignmentOptions $options) {}

    public function supports(I $intent): bool
    {
        return in_array($intent, [I::ViewMyGroups, I::ViewAttendanceList], true);
    }

    public function handle(AgentRequest $request): AgentResponse
    {
        if ($rejection = $this->gate->reject($request, $this->supports($request->facts->intent), true)) {
            return $rejection;
        }
        $facts = $request->facts;
        if ($facts->intent === I::ViewAttendanceList && $facts->assignmentId === null) {
            return $this->options->selection($facts->context);
        }
        $data = $this->options->data($facts->context, $facts->assignmentId);
        $actions = $facts->intent === I::ViewAttendanceList && $data['items']
            ? [['type' => 'navigate', 'route' => 'catequista.mi_grupo', 'parameters' => ['asignacion_id' => $facts->assignmentId]]] : [];

        return new AgentResponse('OK', $facts->intent->value.'_READY',
            $facts->intent === I::ViewAttendanceList ? 'chatbot.attendance_in_group_module' : 'chatbot.groups_ready',
            ['groups' => $data['items']], $actions, ['hasMore' => $data['hasMore']]);
    }
}
