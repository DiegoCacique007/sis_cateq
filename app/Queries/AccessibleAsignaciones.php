<?php

namespace App\Queries;

use App\Enums\CatequesisCapability as C;
use App\Enums\UserRole as R;
use App\Models\Secretaria\AsignaGrupo;
use App\Services\Authorization\AccessContext;
use App\Services\Authorization\CatequesisAccess;
use Illuminate\Database\Eloquent\Builder;

final class AccessibleAsignaciones
{
    public function __construct(private readonly CatequesisAccess $access) {}

    /** Incluye los seis identificadores de la asignación; nunca colapsa por grupo. */
    public function for(AccessContext $context): Builder
    {
        $query = self::valid()->where('asigna_grupo.periodo_id', $context->activePeriodId);
        if (! $this->access->can($context, C::ViewGroups)->isAllowed()) {
            return $query->whereRaw('1 = 0');
        }
        if ($context->role === R::Catequista) {
            $query->where('asigna_grupo.catequista_id', $context->userId);
        }
        if ($context->role === R::CoordinadorComunidades) {
            $query->where('asigna_grupo.comunidad_id', $context->communityId);
        }

        return $query;
    }

    /** Base interna también usada para contar TODAS las asignaciones competidoras. */
    public static function valid(): Builder
    {
        return AsignaGrupo::query()->whereHas('comunidad')->whereHas('grupo')
            ->whereHas('nivel')->whereHas('periodo')->whereHas('catequista');
    }
}
