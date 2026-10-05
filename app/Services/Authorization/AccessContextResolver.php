<?php

namespace App\Services\Authorization;

use App\Models\Secretaria\Periodo;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

final class AccessContextResolver
{
    public function resolve(Request $request): AccessContext
    {
        // Recargar por la identidad autenticada evita confiar en atributos mutados o request input.
        $identity = $request->user();
        $user = $identity instanceof User ? User::query()->find($identity->getAuthIdentifier()) : null;
        if (! $user || ! $user->isApprovedForAccess()) {
            throw new AuthorizationException('UNAUTHORIZED_CONTEXT');
        }

        $periodId = $request->hasSession() ? $request->session()->get('periodo_activo_id') : null;
        $periodId = filter_var($periodId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $periodId = $periodId && Periodo::query()->whereKey($periodId)->exists() ? $periodId : null;

        return new AccessContext(
            (int) $user->id, $user->operationalRole(), $user->status,
            $user->comunidad_id === null ? null : (int) $user->comunidad_id,
            $periodId,
        );
    }
}
