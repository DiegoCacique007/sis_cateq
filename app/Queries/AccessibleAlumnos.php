<?php

namespace App\Queries;

use App\Enums\CatequesisCapability as C;
use App\Enums\UserRole as R;
use App\Models\Secretaria\Alumno;
use App\Services\Authorization\AccessContext;
use App\Services\Authorization\CatequesisAccess;
use Illuminate\Database\Eloquent\Builder;

final class AccessibleAlumnos
{
    public function __construct(private readonly CatequesisAccess $access) {}

    public function for(AccessContext $context): Builder
    {
        $query = Alumno::query()->whereHas('comunidad');
        $capability = $context->role === R::Catequista ? C::ViewGroupStudents : C::ViewStudents;
        if (! $this->access->can($context, $capability)->isAllowed()) {
            return $query->whereRaw('1 = 0');
        }
        if ($context->role === R::CoordinadorComunidades) {
            $query->where('alumnos.comunidad_id', $context->communityId);
        }
        if (in_array($context->role, [R::Catequista, R::CoordinadorComunidades], true)) {
            $query->whereIn('alumnos.id', (new AccessibleInscripciones($this->access))->for($context)
                ->select('inscripciones.alumno_id'));
        }

        return $query;
    }
}
