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

    /** Secretaría conserva administración de inscripciones sin asignación; supervisión usa el alcance académico. */
    public function forIndex(AccessContext $context): Builder
    {
        if (! $this->access->can($context, C::ViewInscriptions)->isAllowed()) {
            return Inscripcion::query()->whereRaw('1 = 0');
        }

        return $context->role === R::Secretaria
            ? $this->forStudentReport($context)->whereHas('grupo')->whereIn('inscripciones.alumno_id', app(AccessibleAlumnos::class)->for($context)->select('alumnos.id'))
            : $this->for($context);
    }

    /** El reporte administrativo conserva alumnos sin asignación inequívoca, sin inferir nivel. */
    public function forStudentReport(AccessContext $context): Builder
    {
        if ($context->role !== R::Secretaria) {
            return $this->for($context);
        }
        $query = Inscripcion::query()->where('inscripciones.periodo_id', $context->activePeriodId)->whereHas('alumno')->whereHas('periodo');

        return $this->access->can($context, C::ViewGroupStudents)->isAllowed() ? $query : $query->whereRaw('1=0');
    }

    private function candidates(AccessContext $context): Builder
    {
        $query = Inscripcion::query()->where('inscripciones.periodo_id', $context->activePeriodId)
            ->whereHas('alumno', fn (Builder $q) => $q->whereHas('comunidad'))
            ->whereHas('grupo')->whereHas('periodo');
        if (! $this->access->can($context, C::ViewGroupStudents)->isAllowed()) {
            return $query->whereRaw('1 = 0');
        }
        if ($context->role === R::CoordinadorComunidades) {
            $query->whereHas('alumno', fn (Builder $q) => $q->where('comunidad_id', $context->communityId));
        }
        $assignments = (new AccessibleAsignaciones($this->access))->for($context)
            ->select('asigna_grupo.grupo_id')
            ->whereColumn('asigna_grupo.periodo_id', 'inscripciones.periodo_id');

        return $query->whereIn('inscripciones.grupo_id', $assignments);
    }

    /** No devuelve registros ni distingue IDs ajenos de inexistentes. */
    public function hasAmbiguousAssignment(AccessContext $context, int $id): bool
    {
        return $this->candidates($context)->whereKey($id)->where($this->assignmentCount(), '>', 1)->exists();
    }

    private function assignmentCount(): Builder
    {
        // No limitar por catequista: ocultaría las asignaciones incompatibles de otros usuarios.
        return AccessibleAsignaciones::valid()->selectRaw('count(*)')
            ->whereColumn('asigna_grupo.grupo_id', 'inscripciones.grupo_id')
            ->whereColumn('asigna_grupo.periodo_id', 'inscripciones.periodo_id');
    }
}
