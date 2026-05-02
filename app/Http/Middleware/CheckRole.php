<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Acepta uno o varios roles separados por pipe (|).
     * Ejemplo de uso en ruta: middleware('role:secretaria|coord_general')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!auth()->check()) {
            abort(403, 'Acceso denegado. No tienes permisos para ver esta página.');
        }

        $userRole = auth()->user()->role;

        // Expandir roles que vengan separados por pipe dentro de un solo argumento
        $allowed = [];
        foreach ($roles as $role) {
            foreach (explode('|', $role) as $r) {
                $allowed[] = trim($r);
            }
        }

        if (!in_array($userRole, $allowed, true)) {
            abort(403, 'Acceso denegado. No tienes permisos para ver esta página.');
        }

        return $next($request);
    }
}