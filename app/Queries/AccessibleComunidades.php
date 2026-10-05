<?php

namespace App\Queries;

use App\Enums\CatequesisCapability as C;
use App\Enums\UserRole as R;
use App\Models\Secretaria\Comunidad;
use App\Services\Authorization\AccessContext;
use App\Services\Authorization\CatequesisAccess;
use Illuminate\Database\Eloquent\Builder;

final class AccessibleComunidades
{
    public function for(AccessContext $context): Builder
    {
        $query = Comunidad::query();
        if (! app(CatequesisAccess::class)->can($context, C::ViewCommunities)->isAllowed()) {
            return $query->whereRaw('1=0');
        }

        return $context->role === R::CoordinadorComunidades ? $query->whereKey($context->communityId) : $query;
    }
}
