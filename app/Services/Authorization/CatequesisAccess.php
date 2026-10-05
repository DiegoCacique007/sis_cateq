<?php

namespace App\Services\Authorization;

use App\Enums\CatequesisCapability as C;
use App\Enums\UserRole as R;
use App\Queries\AccessibleAlumnos;
use App\Queries\AccessibleAsignaciones;
use App\Queries\AccessibleEvaluaciones;
use App\Queries\AccessibleInscripciones;

final class CatequesisAccess
{
    public function can(AccessContext $context, C $capability): AccessDecision
    {
        if ($context->status !== 'aprobado' || $context->userId < 1) {
            return AccessDecision::deny('UNAUTHORIZED_CONTEXT');
        }
        $common = [C::ViewGroups, C::ViewGroupStudents, C::ViewEvaluations, C::ViewBoletas,
            C::ViewStudents, C::ViewCommunities];
        $allowed = match ($context->role) {
            R::Secretaria => [...$common, C::ViewTutors, C::ViewInscriptions, C::ViewLevels,
                C::ManageEvaluations, C::ManageStudents, C::ManageTutors, C::ManageInscriptions,
                C::ManageAssignments, C::ManageCommunities, C::ManagePeriods, C::ManageLevels,
                C::ManageUnits, C::ManageRubrics, C::ManageUsers, C::ManageGroups],
            R::Catequista => [C::ViewGroups, C::ViewGroupStudents, C::ViewAttendanceList,
                C::ViewEvaluations, C::ManageEvaluations],
            R::Parroco, R::CoordinadorComunidades => [...$common, C::ViewCatechists],
            R::CoordinadorGeneral => [...$common, C::ViewTutors, C::ViewInscriptions, C::ViewLevels],
        };
        if (! in_array($capability, $allowed, true)) {
            return AccessDecision::deny('ROLE_NOT_ALLOWED');
        }
        if ($context->role === R::CoordinadorComunidades && ! $context->communityId) {
            return AccessDecision::missing('MISSING_COMMUNITY');
        }
        $needsPeriod = in_array($capability, [C::ViewGroups, C::ViewGroupStudents, C::ViewAttendanceList,
            C::ViewEvaluations, C::ManageEvaluations, C::ViewBoletas], true)
            || ($context->role === R::CoordinadorComunidades && $capability === C::ViewStudents);
        if ($needsPeriod && ! $context->activePeriodId) {
            return AccessDecision::missing('MISSING_PERIOD');
        }

        return AccessDecision::allow();
    }

    public function canViewAsignacion(AccessContext $context, int $id): AccessDecision
    {
        $decision = $this->can($context, C::ViewGroups);

        return $decision->isAllowed()
            ? $this->exists((new AccessibleAsignaciones($this))->for($context)->whereKey($id)->exists()) : $decision;
    }

    public function canViewInscripcion(AccessContext $context, int $id): AccessDecision
    {
        $decision = $this->can($context, C::ViewGroupStudents);
        if (! $decision->isAllowed()) {
            return $decision;
        }
        $query = new AccessibleInscripciones($this);
        // Solo diagnosticar ambigüedad si hay al menos una asignación dentro del alcance.
        if ($query->hasAmbiguousAssignment($context, $id)) {
            return AccessDecision::ambiguous();
        }

        return $this->exists($query->for($context)->whereKey($id)->exists());
    }

    public function canUseAsignacion(AccessContext $context, int $id): AccessDecision
    {
        $decision = $this->canViewAsignacion($context, $id);
        if (! $decision->isAllowed()) {
            return $decision;
        }
        $assignment = (new AccessibleAsignaciones($this))->for($context)->findOrFail($id);

        return AccessibleAsignaciones::valid()->where('grupo_id', $assignment->grupo_id)
            ->where('periodo_id', $assignment->periodo_id)->count() === 1
            ? AccessDecision::allow() : AccessDecision::ambiguous();
    }

    public function canUseInscripcionAsignacion(AccessContext $context, int $inscripcionId, int $asignacionId): AccessDecision
    {
        $decision = $this->canViewInscripcion($context, $inscripcionId);
        if (! $decision->isAllowed()) {
            return $decision;
        }
        $assignment = (new AccessibleAsignaciones($this))->for($context)->whereKey($asignacionId)->get();
        if ($assignment->isEmpty()) {
            return AccessDecision::deny();
        }
        $assignment = $assignment->sole();

        return $this->exists((new AccessibleInscripciones($this))->for($context)->whereKey($inscripcionId)
            ->where('grupo_id', $assignment->grupo_id)->where('periodo_id', $assignment->periodo_id)
            ->whereHas('alumno', fn ($q) => $q->where('comunidad_id', $assignment->comunidad_id))->exists());
    }

    public function canViewAlumno(AccessContext $context, int $id): AccessDecision
    {
        $capability = $context->role === R::Catequista ? C::ViewGroupStudents : C::ViewStudents;
        $decision = $this->can($context, $capability);

        return $decision->isAllowed()
            ? $this->exists((new AccessibleAlumnos($this))->for($context)->whereKey($id)->exists()) : $decision;
    }

    public function canViewEvaluacion(AccessContext $context, int $id): AccessDecision
    {
        return $this->evaluation($context, $id, C::ViewEvaluations);
    }

    public function canManageEvaluacion(AccessContext $context, int $id): AccessDecision
    {
        return $this->evaluation($context, $id, C::ManageEvaluations);
    }

    /** Autoriza el destino de una captura nueva sin confiar en un payload de evaluación. */
    public function canCaptureEvaluacion(AccessContext $context, int $inscripcionId, int $unidadId, int $rubroId): AccessDecision
    {
        $decision = $this->can($context, C::ManageEvaluations);
        if (! $decision->isAllowed()) {
            return $decision;
        }
        $decision = $this->canViewInscripcion($context, $inscripcionId);
        if (! $decision->isAllowed()) {
            return $decision;
        }

        $inscription = (new AccessibleInscripciones($this))->for($context)->whereKey($inscripcionId);
        $matching = (new AccessibleAsignaciones($this))->for($context)
            ->whereIn('asigna_grupo.grupo_id', $inscription->select('inscripciones.grupo_id'))
            ->whereIn('asigna_grupo.nivel_id', \App\Models\Secretaria\Unidad::query()
                ->whereKey($unidadId)->select('unidades.nivel_id'))->exists();

        return $this->exists($matching && \App\Models\Secretaria\Rubro::query()->whereKey($rubroId)->exists());
    }

    public function canViewBoleta(AccessContext $context, int $inscripcionId): AccessDecision
    {
        $decision = $this->can($context, C::ViewBoletas);

        return $decision->isAllowed() ? $this->canViewInscripcion($context, $inscripcionId) : $decision;
    }

    private function evaluation(AccessContext $context, int $id, C $capability): AccessDecision
    {
        $decision = $this->can($context, $capability);

        return $decision->isAllowed()
            ? $this->exists((new AccessibleEvaluaciones($this))->for($context)->whereKey($id)->exists()) : $decision;
    }

    private function exists(bool $exists): AccessDecision
    {
        return $exists ? AccessDecision::allow() : AccessDecision::deny();
    }
}
