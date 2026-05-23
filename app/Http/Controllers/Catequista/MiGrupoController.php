<?php

namespace App\Http\Controllers\Catequista;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class MiGrupoController extends Controller
{
    public function index()
    {
        $catequistaId = auth()->id();
        $periodoActivoId = session('periodo_activo_id');

        $asignacionesQuery = DB::table('asigna_grupo')
            ->join('comunidades', 'asigna_grupo.comunidad_id', '=', 'comunidades.id')
            ->join('grupos', 'asigna_grupo.grupo_id', '=', 'grupos.id')
            ->join('niveles', 'asigna_grupo.nivel_id', '=', 'niveles.id')
            ->join('periodos', 'asigna_grupo.periodo_id', '=', 'periodos.id')
            ->join('users', 'asigna_grupo.catequista_id', '=', 'users.id')
            ->where('asigna_grupo.catequista_id', $catequistaId)
            ->where('asigna_grupo.periodo_id', $periodoActivoId)
            ->whereNull('asigna_grupo.deleted_at')
            ->whereNull('comunidades.deleted_at')
            ->whereNull('grupos.deleted_at')
            ->whereNull('niveles.deleted_at')
            ->whereNull('periodos.deleted_at')
            ->select(
                'asigna_grupo.id as asignacion_id',
                'asigna_grupo.grupo_id',
                'asigna_grupo.periodo_id',
                'comunidades.comunidad',
                'grupos.nombre as grupo',
                'niveles.nivel',
                'users.name as catequista_nombre',
                DB::raw("CONCAT(periodos.fecha_inicio, ' al ', periodos.fecha_fin) as periodo"),
                DB::raw("CONCAT(niveles.nivel, ' - Grupo ', grupos.nombre, ' (', comunidades.comunidad, ')') as texto_asignacion")
            );

        $asignaciones = $asignacionesQuery->get();
        $asignacionId = request('asignacion_id');

        if ($asignacionId) {
            $asignacion = $asignaciones->firstWhere('asignacion_id', (int) $asignacionId);
        } else {
            $asignacion = $asignaciones->first();
            $asignacionId = $asignacion ? $asignacion->asignacion_id : null;
        }

        $alumnos = collect();

        if ($asignacion) {
            $alumnos = DB::table('inscripciones')
                ->join('alumnos', 'inscripciones.alumno_id', '=', 'alumnos.id')
                ->where('inscripciones.grupo_id', $asignacion->grupo_id)
                ->where('inscripciones.periodo_id', $asignacion->periodo_id)
                ->where('inscripciones.estado', 1)
                ->whereNull('inscripciones.deleted_at')
                ->whereNull('alumnos.deleted_at')
                ->select(
                    'alumnos.id',
                    DB::raw("TRIM(CONCAT(alumnos.nombre, ' ', alumnos.apellido_paterno, ' ', COALESCE(alumnos.apellido_materno, ''))) as alumno")
                )
                ->groupBy(
                    'alumnos.id',
                    'alumnos.nombre',
                    'alumnos.apellido_paterno',
                    'alumnos.apellido_materno'
                )
                ->orderBy('alumnos.apellido_paterno')
                ->orderBy('alumnos.apellido_materno')
                ->orderBy('alumnos.nombre')
                ->get();
        }

        return view('catequista.mi_grupo', compact(
            'asignaciones',
            'asignacionId',
            'asignacion',
            'alumnos'
        ));
    }

    public function exportarAsistenciaPdf()
    {
        $catequistaId = auth()->id();
        $periodoActivoId = session('periodo_activo_id');

        if (!$periodoActivoId) {
            return back()->with('error', 'No hay un periodo activo seleccionado.');
        }

        $asignacionesQuery = DB::table('asigna_grupo')
            ->join('comunidades', 'asigna_grupo.comunidad_id', '=', 'comunidades.id')
            ->join('grupos', 'asigna_grupo.grupo_id', '=', 'grupos.id')
            ->join('niveles', 'asigna_grupo.nivel_id', '=', 'niveles.id')
            ->join('periodos', 'asigna_grupo.periodo_id', '=', 'periodos.id')
            ->join('users', 'asigna_grupo.catequista_id', '=', 'users.id')
            ->where('asigna_grupo.catequista_id', $catequistaId)
            ->where('asigna_grupo.periodo_id', $periodoActivoId)
            ->whereNull('asigna_grupo.deleted_at')
            ->whereNull('comunidades.deleted_at')
            ->whereNull('grupos.deleted_at')
            ->whereNull('niveles.deleted_at')
            ->whereNull('periodos.deleted_at')
            ->select(
                'asigna_grupo.id as asignacion_id',
                'asigna_grupo.grupo_id',
                'asigna_grupo.periodo_id',
                'comunidades.comunidad',
                'grupos.nombre as grupo',
                'niveles.nivel',
                'users.name as catequista_nombre',
                DB::raw("CONCAT(periodos.fecha_inicio, ' al ', periodos.fecha_fin) as periodo")
            );

        $asignaciones = $asignacionesQuery->get();
        $asignacionId = request('asignacion_id');

        if ($asignacionId) {
            $asignacion = $asignaciones->firstWhere('asignacion_id', (int) $asignacionId);
        } else {
            $asignacion = $asignaciones->first();
        }

        if (!$asignacion) {
            return back()->with('error', 'No tienes un grupo asignado para este periodo.');
        }

        $alumnos = DB::table('inscripciones')
            ->join('alumnos', 'inscripciones.alumno_id', '=', 'alumnos.id')
            ->where('inscripciones.grupo_id', $asignacion->grupo_id)
            ->where('inscripciones.periodo_id', $asignacion->periodo_id)
            ->where('inscripciones.estado', 1)
            ->whereNull('inscripciones.deleted_at')
            ->whereNull('alumnos.deleted_at')
            ->select(
                DB::raw("TRIM(CONCAT(alumnos.apellido_paterno, ' ', COALESCE(alumnos.apellido_materno, ''), ' ', alumnos.nombre)) as alumno")
            )
            ->orderBy('alumnos.apellido_paterno')
            ->orderBy('alumnos.apellido_materno')
            ->orderBy('alumnos.nombre')
            ->get();

        $pdf = Pdf::loadView('catequista.pdf.asistencia', compact('asignacion', 'alumnos'))
            ->setPaper('letter', 'landscape');

        return $pdf->download('lista_asistencia_catequesis.pdf');
    }
}
