<?php

namespace App\Queries;

use App\Enums\CatequesisCapability as C;
use App\Models\Secretaria\Evaluacion;
use App\Services\Authorization\AccessContext;
use App\Services\Authorization\CatequesisAccess;
use Illuminate\Database\Eloquent\Builder;

final class AccessibleEvaluaciones
{
    public function __construct(private readonly CatequesisAccess $access) {}

    public function for(AccessContext $context): Builder
    {
        $query = Evaluacion::query()->whereHas('rubro')->whereHas('unidad', fn (Builder $q) => $q->whereHas('nivel'));
        if (! $this->access->can($context, C::ViewEvaluations)->isAllowed()) {
            return $query->whereRaw('1 = 0');
        }

        $inscriptions = (new AccessibleInscripciones($this->access))->for($context)
            ->select('inscripciones.id')
            ->whereIn('inscripciones.grupo_id', AccessibleAsignaciones::valid()->select('asigna_grupo.grupo_id')
                ->whereColumn('asigna_grupo.periodo_id', 'inscripciones.periodo_id')
                ->whereIn('asigna_grupo.nivel_id', \App\Models\Secretaria\Unidad::query()->select('unidades.nivel_id')
                    ->whereColumn('unidades.id', 'evaluaciones.unidad_id')));

        // El guardado actual del catequista omite periodo_id: NULL hereda el de la inscripción.
        return $query->whereIn('evaluaciones.inscripcion_id', $inscriptions)
            ->where(fn (Builder $q) => $q->where('evaluaciones.periodo_id', $context->activePeriodId)
                ->orWhereNull('evaluaciones.periodo_id'));
    }
}
