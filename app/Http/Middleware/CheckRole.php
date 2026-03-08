<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Si no está logueado, o su rol no coincide con el que requiere la ruta, lo bloqueamos
        if (!auth()->check() || auth()->user()->role !== $role) {
            abort(403, 'Acceso denegado. No tienes permisos para ver esta página.');
        }

        return $next($request);
    }
}