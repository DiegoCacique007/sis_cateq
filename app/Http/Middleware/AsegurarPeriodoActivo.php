<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

class AsegurarPeriodoActivo
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session()->has('periodo_activo_id')) {
            $periodo = DB::table('periodos')
                ->whereNull('deleted_at')
                ->orderBy('fecha_inicio', 'desc')
                ->first();

            if ($periodo) {
                $nombrePeriodo = $periodo->fecha_inicio . ' al ' . $periodo->fecha_fin;
                session([
                    'periodo_activo_id' => $periodo->id,
                    'periodo_activo_nombre' => $nombrePeriodo
                ]);
            }
        }

        // Compartir periodos con todas las vistas que necesiten el modal de periodo
        if (auth()->check() && in_array(auth()->user()->role, ['secretaria', 'catequista', 'coord_comunidad', 'coord_general', 'parroco'])) {
            $periodosGlobales = DB::table('periodos')
                ->whereNull('deleted_at')
                ->orderBy('fecha_inicio', 'desc')
                ->get()
                ->map(function ($p) {
                    $p->nombre = $p->fecha_inicio . ' al ' . $p->fecha_fin;
                    return $p;
                });
            View::share('periodos_globales', $periodosGlobales);
        }

        return $next($request);
    }
}
