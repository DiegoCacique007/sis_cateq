<?php

namespace App\Http\Middleware;

use App\Models\Secretaria\Periodo;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class AsegurarPeriodoActivo
{
    public function handle(Request $request, Closure $next): Response
    {
        $habiaPeriodo = session()->has('periodo_activo_id');
        $periodoActivo = $habiaPeriodo
            ? DB::table('periodos')->where('id', session('periodo_activo_id'))->whereNull('deleted_at')->first()
            : null;

        // Sin periodo o con uno eliminado: el activo más reciente; si no hay activos, el más reciente.
        if (!$periodoActivo) {
            $periodoActivo = DB::table('periodos')->whereNull('deleted_at')
                ->orderByRaw('CASE WHEN estado = 1 THEN 0 ELSE 1 END')->orderBy('fecha_inicio', 'desc')->first();

            if ($periodoActivo) {
                session(['periodo_activo_id' => $periodoActivo->id]);
                if ($habiaPeriodo) {
                    session()->now('error', 'El periodo que tenías seleccionado ya no está disponible. Ahora trabajas en '
                        .Periodo::nombreLegible($periodoActivo->fecha_inicio, $periodoActivo->fecha_fin).'.');
                }
            } else {
                session()->forget(['periodo_activo_id', 'periodo_activo_nombre']);
            }
        }

        if ($periodoActivo) {
            session(['periodo_activo_nombre' => Periodo::nombreLegible($periodoActivo->fecha_inicio, $periodoActivo->fecha_fin)]);
        }

        if (auth()->check() && in_array(auth()->user()->role, ['secretaria', 'catequista', 'coordinador_comunidades', 'coordinador_general', 'parroco'])) {
            $periodosGlobales = DB::table('periodos')->whereNull('deleted_at')->orderBy('fecha_inicio', 'desc')->get()->map(function ($periodo) {
                $periodo->nombre = Periodo::nombreLegible($periodo->fecha_inicio, $periodo->fecha_fin);
                return $periodo;
            });

            View::share('periodos_globales', $periodosGlobales);
        }

        return $next($request);
    }
}
