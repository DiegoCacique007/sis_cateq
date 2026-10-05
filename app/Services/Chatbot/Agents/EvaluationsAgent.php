<?php

namespace App\Services\Chatbot\Agents;

use App\Enums\ChatbotIntent as I;
use App\Queries\AccessibleAsignaciones;
use App\Queries\AccessibleEvaluaciones;
use App\Queries\AccessibleInscripciones;
use App\Services\Chatbot\Contracts\Agent;

final class EvaluationsAgent implements Agent
{
    public function __construct(
        private readonly AgentGate $gate,
        private readonly AssignmentOptions $options,
        private readonly AccessibleEvaluaciones $evaluations,
        private readonly AccessibleAsignaciones $assignments,
        private readonly AccessibleInscripciones $inscriptions,
    ) {}

    public function supports(I $intent): bool
    {
        return $intent === I::ViewEvaluations;
    }

    public function handle(AgentRequest $request): AgentResponse
    {
        if ($rejection = $this->gate->reject($request, $this->supports($request->facts->intent))) {
            return $rejection;
        }
        $facts = $request->facts;
        if (! $facts->resources()) {
            return $this->options->selection($facts->context);
        }
        $inscriptions = $this->inscriptions->for($facts->context)
            ->when($facts->inscriptionId !== null, fn ($q) => $q->whereKey($facts->inscriptionId))
            ->when($facts->studentId !== null, fn ($q) => $q->where('alumno_id', $facts->studentId));
        if ($facts->assignmentId !== null) {
            $inscriptions->whereIn('grupo_id', $this->assignments->for($facts->context)->whereKey($facts->assignmentId)->select('grupo_id'));
        }
        $rows = $this->evaluations->for($facts->context)
            ->whereIn('inscripcion_id', $inscriptions->select('inscripciones.id'))
            ->when($facts->evaluationId !== null, fn ($q) => $q->whereKey($facts->evaluationId))
            ->select(['id', 'inscripcion_id', 'unidad_id', 'rubro_id', 'calificacion'])
            ->with(['inscripcion:id,alumno_id', 'inscripcion.alumno:id,nombre,apellido_paterno,apellido_materno', 'unidad:id,nombre', 'rubro:id,nombre'])
            ->orderBy('id')->limit(101)->get();

        return new AgentResponse('OK', 'EVALUATIONS_READY', 'chatbot.evaluations_ready', [
            'evaluations' => $rows->take(100)->map(fn ($e) => [
                'student' => ['id' => (int) $e->inscripcion->alumno->id, 'fullName' => $e->inscripcion->alumno->nombre_completo],
                'unit' => $e->unidad->nombre, 'rubric' => $e->rubro->nombre, 'grade' => $e->calificacion,
            ])->values()->all(),
        ], metadata: ['hasMore' => $rows->count() > 100]);
    }
}
