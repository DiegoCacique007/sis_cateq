<?php

namespace App\Http\Middleware;

use Closure;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class AsegurarPeriodoActivo
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session()->has('periodo_activo_id')) {
            $periodo = DB::table('periodos')->whereNull('deleted_at')->orderBy('fecha_inicio', 'desc')->first();

            if ($periodo) {
                session([
                    'periodo_activo_id' => $periodo->id,
                    'periodo_activo_nombre' => $this->formatearPeriodo($periodo->fecha_inicio, $periodo->fecha_fin),
                ]);
            }
        }

        if (session()->has('periodo_activo_id')) {
            $periodoActivo = DB::table('periodos')->where('id', session('periodo_activo_id'))->whereNull('deleted_at')->first();

            if ($periodoActivo) {
                session([
                    'periodo_activo_nombre' => $this->formatearPeriodo($periodoActivo->fecha_inicio, $periodoActivo->fecha_fin),
                ]);
            }
        }

        if (auth()->check() && in_array(auth()->user()->role, ['secretaria', 'catequista', 'coordinador_comunidades', 'coordinador_general', 'parroco'])) {
            $periodosGlobales = DB::table('periodos')->whereNull('deleted_at')->orderBy('fecha_inicio', 'desc')->get()->map(function ($periodo) {
                $periodo->nombre = $this->formatearPeriodo($periodo->fecha_inicio, $periodo->fecha_fin);
                return $periodo;
            });

            View::share('periodos_globales', $periodosGlobales);
        }

        return $next($request);
    }

    private function formatearPeriodo($fechaInicio, $fechaFin): string
    {
        $inicio = Carbon::parse($fechaInicio)->locale('es')->translatedFormat('F Y');
        $fin = Carbon::parse($fechaFin)->locale('es')->translatedFormat('F Y');

        return ucfirst($inicio) . ' - ' . ucfirst($fin);
    }
}
