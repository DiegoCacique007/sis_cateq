<?php

namespace App\Services\Chatbot\Agents;

use App\Enums\ChatbotIntent as I;
use App\Queries\AccessibleAlumnos;
use App\Queries\AccessibleAsignaciones;
use App\Queries\AccessibleInscripciones;
use App\Services\Chatbot\Contracts\Agent;

final class StudentsAgent implements Agent
{
    public function __construct(
        private readonly AgentGate $gate,
        private readonly AssignmentOptions $options,
        private readonly AccessibleAlumnos $students,
        private readonly AccessibleAsignaciones $assignments,
        private readonly AccessibleInscripciones $inscriptions,
    ) {}

    public function supports(I $intent): bool
    {
        return $intent === I::ViewGroupStudents;
    }

    public function handle(AgentRequest $request): AgentResponse
    {
        if ($rejection = $this->gate->reject($request, $this->supports($request->facts->intent), true)) {
            return $rejection;
        }
        $facts = $request->facts;
        if ($facts->assignmentId === null) {
            return $this->options->selection($facts->context);
        }
        $groups = $this->assignments->for($facts->context)->whereKey($facts->assignmentId)->select('grupo_id');
        $inscriptions = $this->inscriptions->for($facts->context)->whereIn('grupo_id', $groups)->select('alumno_id');
        $rows = $this->students->for($facts->context)->whereIn('alumnos.id', $inscriptions)
            ->select(['id', 'nombre', 'apellido_paterno', 'apellido_materno'])->orderBy('id')->limit(101)->get();

        return new AgentResponse('OK', 'STUDENTS_READY', 'chatbot.students_ready', [
            'students' => $rows->take(100)->map(fn ($a) => ['id' => (int) $a->id, 'fullName' => $a->nombre_completo])->values()->all(),
        ], metadata: ['hasMore' => $rows->count() > 100]);
    }
}
