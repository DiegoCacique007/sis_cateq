<?php

namespace App\Services\Chatbot\Agents;

use App\Queries\AccessibleAsignaciones;
use App\Services\Authorization\AccessContext;
use App\Services\Authorization\CatequesisAccess;

/** Proyección común de asignaciones; máximo 100 opciones por respuesta. */
final class AssignmentOptions
{
    public function __construct(private readonly AccessibleAsignaciones $assignments, private readonly CatequesisAccess $access) {}

    public function data(AccessContext $context, ?int $id = null): array
    {
        $rows = $this->assignments->for($context)->when($id !== null, fn ($q) => $q->whereKey($id))
            ->select(['id', 'grupo_id', 'nivel_id', 'comunidad_id', 'periodo_id'])
            ->with(['grupo:id,nombre', 'nivel:id,nivel', 'comunidad:id,comunidad', 'periodo:id,fecha_inicio,fecha_fin'])
            ->orderBy('id')->limit(101)->get();

        return [
            'items' => $rows->take(100)->map(fn ($a) => [
                'assignmentId' => (int) $a->id, 'groupId' => (int) $a->grupo_id,
                'name' => $a->grupo->nombre, 'level' => $a->nivel->nivel,
                'community' => $a->comunidad->comunidad,
                'period' => $a->periodo->fecha_inicio->format('Y-m-d').' / '.$a->periodo->fecha_fin->format('Y-m-d'),
            ])->values()->all(),
            'hasMore' => $rows->count() > 100,
        ];
    }

    public function selection(AccessContext $context): AgentResponse
    {
        $data = $this->data($context);
        // Ya limitadas en SQL por AccessibleAsignaciones. La autoridad descarta opciones inconsistentes.
        $options = array_values(array_filter($data['items'], fn ($item) => $this->access->canUseAsignacion($context, $item['assignmentId'])->isAllowed()));

        return new AgentResponse('RESOURCE_SELECTION_REQUIRED', 'ASSIGNMENT_SELECTION_REQUIRED', 'chatbot.select_assignment',
            ['resourceType' => 'assignment', 'options' => $options],
            [['type' => 'select_resource', 'resourceType' => 'assignment']], ['hasMore' => $data['hasMore']]);
    }
}
