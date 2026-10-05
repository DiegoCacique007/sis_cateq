<?php

namespace App\Http\Support;

use App\Enums\CatequesisCapability;
use App\Services\Authorization\AccessContext;
use App\Services\Authorization\AccessContextResolver;
use App\Services\Authorization\AccessDecision;
use App\Services\Authorization\CatequesisAccess;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;

final class CatequesisHttp
{
    public function context(Request $request, CatequesisCapability $capability): AccessContext
    {
        $context = app(AccessContextResolver::class)->resolve($request);
        $this->enforce(app(CatequesisAccess::class)->can($context, $capability));

        return $context;
    }

    public function enforce(AccessDecision $decision): void
    {
        if ($decision->isAllowed()) {
            return;
        }
        if ($decision->state === 'missing_context') {
            $message = $decision->code === 'MISSING_PERIOD'
                ? 'Selecciona un periodo válido para continuar.' : 'Solicita a Secretaría que revise tu comunidad asignada.';
            throw new HttpResponseException(redirect()->route('welcome')->with('error', $message));
        }
        abort(match ($decision->state) {
            'ambiguous' => 409,
            default => $decision->code === 'OUTSIDE_SCOPE' ? 404 : 403,
        }, $decision->state === 'ambiguous'
            ? 'No es posible identificar una asignación única. Solicita revisión a Secretaría.' : 'Recurso no disponible.');
    }
}
