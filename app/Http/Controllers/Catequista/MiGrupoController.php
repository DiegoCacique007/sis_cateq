<?php

namespace App\Http\Controllers\Catequista;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class MiGrupoController extends Controller
{
    public function index()
    {
        $catequistaId = auth()->id();

        $asignacionesQuery = DB::table('asigna_grupo')
            ->join('comunidades', 'asigna_grupo.comunidad_id', '=', 'comunidades.id')
            ->join('grupos', 'asigna_grupo.grupo_id', '=', 'grupos.id')
            ->join('niveles', 'asigna_grupo.nivel_id', '=', 'niveles.id')
            ->join('periodos', 'asigna_grupo.periodo_id', '=', 'periodos.id')
            ->where('asigna_grupo.catequista_id', $catequistaId)
            ->where('asigna_grupo.periodo_id', session('periodo_activo_id'))
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
                ->orderBy('alumnos.nombre')
                ->orderBy('alumnos.apellido_paterno')
                ->get();
        }

        return view('catequista.mi_grupo', compact('asignaciones', 'asignacionId', 'asignacion', 'alumnos'));
    }

    public function exportarAsistencia()
    {
        $catequistaId = auth()->id();

        $asignacionesQuery = DB::table('asigna_grupo')
            ->join('comunidades', 'asigna_grupo.comunidad_id', '=', 'comunidades.id')
            ->join('grupos', 'asigna_grupo.grupo_id', '=', 'grupos.id')
            ->join('niveles', 'asigna_grupo.nivel_id', '=', 'niveles.id')
            ->join('periodos', 'asigna_grupo.periodo_id', '=', 'periodos.id')
            ->where('asigna_grupo.catequista_id', $catequistaId)
            ->where('asigna_grupo.periodo_id', session('periodo_activo_id'))
            ->whereNull('asigna_grupo.deleted_at')
            ->whereNull('comunidades.deleted_at')
            ->whereNull('grupos.deleted_at')
            ->whereNull('niveles.deleted_at')
            ->whereNull('periodos.deleted_at')
            ->select(
                'asigna_grupo.id as asignacion_id',
                'comunidades.comunidad',
                'grupos.nombre as grupo',
                'niveles.nivel',
                'asigna_grupo.grupo_id',
                'asigna_grupo.periodo_id',
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
            return back()->with('error', 'No tienes un grupo asignado.');
        }

        $alumnos = DB::table('inscripciones')
            ->join('alumnos', 'inscripciones.alumno_id', '=', 'alumnos.id')
            ->where('inscripciones.grupo_id', $asignacion->grupo_id)
            ->where('inscripciones.periodo_id', $asignacion->periodo_id)
            ->whereNull('inscripciones.deleted_at')
            ->whereNull('alumnos.deleted_at')
            ->select(
                DB::raw("TRIM(CONCAT(alumnos.nombre, ' ', alumnos.apellido_paterno, ' ', COALESCE(alumnos.apellido_materno, ''))) as alumno")
            )
            ->orderBy('alumnos.nombre')
            ->orderBy('alumnos.apellido_paterno')
            ->get();

        $fileName = 'Asistencia_' . str_replace(' ', '_', $asignacion->grupo) . '.xls';

        return response(view('catequista.excel_asistencia', compact('asignacion', 'alumnos')))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }
}
