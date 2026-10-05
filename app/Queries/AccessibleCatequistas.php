<?php

namespace App\Queries;

use App\Enums\CatequesisCapability as C;
use App\Enums\UserRole as R;
use App\Models\User;
use App\Services\Authorization\AccessContext;
use App\Services\Authorization\CatequesisAccess;
use Illuminate\Database\Eloquent\Builder;

final class AccessibleCatequistas
{
    public function for(AccessContext $context): Builder
    {
        $query = User::query()->where('role', R::Catequista->value)->where('status', 'aprobado');
        // También sirve como catálogo reducido de filtros de boletas.
        $access = app(CatequesisAccess::class);
        if (! $access->can($context, C::ViewCatechists)->isAllowed() && ! $access->can($context, C::ViewBoletas)->isAllowed()) {
            return $query->whereRaw('1=0');
        }

        return $query->whereIn('users.id', app(AccessibleAsignaciones::class)->for($context)->select('asigna_grupo.catequista_id'));
    }
}
