<?php

namespace App\Queries;

use App\Enums\CatequesisCapability as C;
use App\Models\Secretaria\Tutor;
use App\Services\Authorization\AccessContext;
use App\Services\Authorization\CatequesisAccess;
use Illuminate\Database\Eloquent\Builder;

final class AccessibleTutores
{
    public function for(AccessContext $context): Builder
    {
        $query = Tutor::query();
        if (! app(CatequesisAccess::class)->can($context, C::ViewTutors)->isAllowed()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('tutores.alumno_id', app(AccessibleAlumnos::class)->forIndex($context)->select('alumnos.id'));
    }
}
