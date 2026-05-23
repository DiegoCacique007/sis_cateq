<?php

namespace App\Http\Controllers\Catequista;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CatequistaController extends Controller
{
    public function index()
    {
        $catequistaId = auth()->id();
        $periodoActivoId = session('periodo_activo_id');

        $totalGruposAsignados = DB::table('asigna_grupo')
            ->where('catequista_id', $catequistaId)
            ->where('periodo_id', $periodoActivoId)
            ->whereNull('deleted_at')
            ->count();

        $totalAlumnosGrupo = DB::table('inscripciones')
            ->join('asigna_grupo', function ($join) {
                $join->on('inscripciones.grupo_id', '=', 'asigna_grupo.grupo_id')
                    ->on('inscripciones.periodo_id', '=', 'asigna_grupo.periodo_id');
            })
            ->join('alumnos', 'inscripciones.alumno_id', '=', 'alumnos.id')
            ->where('asigna_grupo.catequista_id', $catequistaId)
            ->where('inscripciones.periodo_id', $periodoActivoId)
            ->where('inscripciones.estado', 1)
            ->whereNull('inscripciones.deleted_at')
            ->whereNull('asigna_grupo.deleted_at')
            ->whereNull('alumnos.deleted_at')
            ->distinct()
            ->count('inscripciones.alumno_id');

        $totalNivelesAsignados = DB::table('asigna_grupo')
            ->where('catequista_id', $catequistaId)
            ->where('periodo_id', $periodoActivoId)
            ->whereNull('deleted_at')
            ->distinct()
            ->count('nivel_id');

        $evaluacionesQuery = DB::table('evaluaciones')
            ->join('inscripciones', 'evaluaciones.inscripcion_id', '=', 'inscripciones.id')
            ->join('asigna_grupo', function ($join) {
                $join->on('inscripciones.grupo_id', '=', 'asigna_grupo.grupo_id')
                    ->on('inscripciones.periodo_id', '=', 'asigna_grupo.periodo_id');
            })
            ->where('asigna_grupo.catequista_id', $catequistaId)
            ->where('inscripciones.periodo_id', $periodoActivoId)
            ->where('inscripciones.estado', 1)
            ->whereNull('inscripciones.deleted_at')
            ->whereNull('asigna_grupo.deleted_at');

        if (Schema::hasColumn('evaluaciones', 'deleted_at')) {
            $evaluacionesQuery->whereNull('evaluaciones.deleted_at');
        }

        $totalEvaluacionesRegistradas = $evaluacionesQuery->count();

        return view('catequista.dashboard', compact(
            'totalGruposAsignados',
            'totalAlumnosGrupo',
            'totalNivelesAsignados',
            'totalEvaluacionesRegistradas'
        ));
    }
}
