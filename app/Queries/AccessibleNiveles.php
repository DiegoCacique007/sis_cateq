<?php

namespace App\Queries;

use App\Enums\CatequesisCapability as C;
use App\Models\Secretaria\Nivel;
use App\Services\Authorization\AccessContext;
use App\Services\Authorization\CatequesisAccess;
use Illuminate\Database\Eloquent\Builder;

final class AccessibleNiveles
{
    public function for(AccessContext $context): Builder
    {
        $query = Nivel::query();

        return app(CatequesisAccess::class)->can($context, C::ViewLevels)->isAllowed()
            ? $query : $query->whereRaw('1 = 0');
    }
}
