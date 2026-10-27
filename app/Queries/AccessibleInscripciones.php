<?php

namespace App\Queries;

use App\Enums\CatequesisCapability as C;
use App\Enums\UserRole as R;
use App\Models\Secretaria\Inscripcion;
use App\Services\Authorization\AccessContext;
use App\Services\Authorization\CatequesisAccess;
use Illuminate\Database\Eloquent\Builder;

final class AccessibleInscripciones
{
    public function __construct(private readonly CatequesisAccess $access) {}

    public function for(AccessContext $context): Builder
    {
        return $this->candidates($context)->where($this->assignmentCount(), '=', 1);
    }

    public function forBoletas(AccessContext $context): Builder
    {
        $query = $this->candidates($context, true);
        if (! $this->access->can($context, C::ViewBoletas)->isAllowed()) {
            return $query->whereRaw('1 = 0');
        }

        $asignacionesAccesibles = (new AccessibleAsignaciones($this->access))->for($context)
            ->select('asigna_grupo.id')
            ->whereColumn('asigna_grupo.grupo_id', 'inscripciones.grupo_id')
            ->whereColumn('asigna_grupo.periodo_id', 'inscripciones.periodo_id');

        $comunidadesAcademicas = AccessibleAsignaciones::valid()
            ->selectRaw('count(distinct asigna_grupo.comunidad_id)')
            ->whereColumn('asigna_grupo.grupo_id', 'inscripciones.grupo_id')
            ->whereColumn('asigna_grupo.periodo_id', 'inscripciones.periodo_id');

        return $query->where(function (Builder $q) use ($asignacionesAccesibles, $comunidadesAcademicas) {
            $q->whereIn('inscripciones.asigna_grupo_id', $asignacionesAccesibles)
                ->orWhere(function (Builder $q) use ($comunidadesAcademicas) {
                    $q->whereNull('inscripciones.asigna_grupo_id')->where($comunidadesAcademicas, '=', 1);
                });
        });
    }

    public function forIndex(AccessContext $context): Builder
    {
        if (! $this->access->can($context, C::ViewInscriptions)->isAllowed()) {
            return Inscripcion::query()->whereRaw('1 = 0');
        }

        return $context->role === R::Secretaria
            ? $this->forStudentReport($context)->whereHas('grupo')
                ->whereIn('inscripciones.alumno_id', app(AccessibleAlumnos::class)->for($context)->select('alumnos.id'))
            : $this->for($context);
    }

    public function forStudentReport(AccessContext $context): Builder
    {
        if ($context->role !== R::Secretaria) {
            return $this->for($context);
        }
        $query = Inscripcion::query()->where('inscripciones.periodo_id', $context->activePeriodId)
            ->whereHas('alumno')->whereHas('periodo');

        return $this->access->can($context, C::ViewGroupStudents)->isAllowed()
            ? $query : $query->whereRaw('1 = 0');
    }

    private function candidates(AccessContext $context, bool $forBoletas = false): Builder
    {
        $query = Inscripcion::query()->where('inscripciones.periodo_id', $context->activePeriodId)
            ->whereHas('alumno', fn (Builder $q) => $q->whereHas('comunidad'))
            ->whereHas('grupo')->whereHas('periodo');
        if (! $this->access->can($context, C::ViewGroupStudents)->isAllowed()) {
            return $query->whereRaw('1 = 0');
        }
        if ($context->role === R::CoordinadorComunidades && ! $forBoletas) {
            $query->whereHas('alumno', fn (Builder $q) => $q->where('comunidad_id', $context->communityId));
        }
        $asignaciones = (new AccessibleAsignaciones($this->access))->for($context)
            ->select('asigna_grupo.grupo_id')
            ->whereColumn('asigna_grupo.periodo_id', 'inscripciones.periodo_id');

        return $query->whereIn('inscripciones.grupo_id', $asignaciones);
    }

    public function hasAmbiguousAssignment(AccessContext $context, int $id): bool
    {
        return $this->candidates($context)->whereKey($id)
            ->where($this->assignmentCount(), '>', 1)->exists();
    }

    private function assignmentCount(): Builder
    {
        return AccessibleAsignaciones::valid()->selectRaw('count(*)')
            ->whereColumn('asigna_grupo.grupo_id', 'inscripciones.grupo_id')
            ->whereColumn('asigna_grupo.periodo_id', 'inscripciones.periodo_id');
    }
}
