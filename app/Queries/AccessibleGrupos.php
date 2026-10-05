<?php

namespace App\Queries;

use App\Enums\CatequesisCapability as C;
use App\Enums\UserRole as R;
use App\Models\Secretaria\Grupo;
use App\Services\Authorization\AccessContext;
use App\Services\Authorization\CatequesisAccess;
use Illuminate\Database\Eloquent\Builder;

final class AccessibleGrupos
{
    public function for(AccessContext $context): Builder
    {
        $query = Grupo::query();
        if (! app(CatequesisAccess::class)->can($context, C::ViewGroups)->isAllowed()) {
            return $query->whereRaw('1=0');
        }
        if (in_array($context->role, [R::Catequista, R::CoordinadorComunidades], true)) {
            return $query->whereIn('grupos.id', app(AccessibleAsignaciones::class)->for($context)->select('asigna_grupo.grupo_id'));
        }

        return $query->where(fn ($q) => $q->where('periodo_id', $context->activePeriodId)->orWhereNull('periodo_id'));
    }
}
