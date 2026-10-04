<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Acepta uno o varios roles separados por pipe (|).
     * Los valores permitidos deben corresponder a UserRole.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $userRole = $request->user()?->operationalRole();

        if ($userRole === null) {
            abort(403, 'Acceso denegado. No tienes permisos para ver esta página.');
        }

        // Expandir roles que vengan separados por pipe dentro de un solo argumento
        $allowed = [];
        foreach ($roles as $role) {
            foreach (explode('|', $role) as $r) {
                $allowedRole = UserRole::tryFrom(trim($r));
                if ($allowedRole !== null) {
                    $allowed[] = $allowedRole;
                }
            }
        }

        if (!in_array($userRole, $allowed, true)) {
            abort(403, 'Acceso denegado. No tienes permisos para ver esta página.');
        }

        return $next($request);
    }
}
