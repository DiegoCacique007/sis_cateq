<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $periodoActivoId = session('periodo_activo_id');

        $totalAlumnos = DB::table('alumnos')
            ->whereNull('deleted_at')
            ->count();

        $totalInscripcionesActivas = DB::table('inscripciones')
            ->whereNull('deleted_at')
            ->when($periodoActivoId, function ($query) use ($periodoActivoId) {
                $query->where('periodo_id', $periodoActivoId);
            })
            ->where('estado', 1)
            ->count();

        $totalGruposAsignados = DB::table('asigna_grupo')
            ->whereNull('deleted_at')
            ->when($periodoActivoId, function ($query) use ($periodoActivoId) {
                $query->where('periodo_id', $periodoActivoId);
            })
            ->count();

        $totalCatequistas = DB::table('users')
            ->where('role', 'catequista')
            ->where('status', 'aprobado')
            ->count();

        $usuariosPendientes = DB::table('users')
            ->where('status', 'pendiente')
            ->count();

        return view('secretaria.dashboard', compact(
            'totalAlumnos',
            'totalInscripcionesActivas',
            'totalGruposAsignados',
            'totalCatequistas',
            'usuariosPendientes'
        ));
    }
}
