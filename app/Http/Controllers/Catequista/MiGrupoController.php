<?php

namespace App\Http\Controllers\Catequista;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class MiGrupoController extends Controller
{
    public function index()
    {
        // 1. Obtenemos el ID de la catequista que inició sesión
        $idCatequista = auth()->id();

        // 2. Realizamos la consulta filtrando por la asignación de grupo
        // Basado en tu estructura: asigna_grupo vincula al catequista con el grupo.
        $alumnos = DB::table('alumnos')
            ->join('inscripciones', 'alumnos.id', '=', 'inscripciones.alumno_id')
            ->join('asigna_grupo', 'inscripciones.grupo_id', '=', 'asigna_grupo.grupo_id')
            ->where('asigna_grupo.catequista_id', $idCatequista)
            ->whereNull('alumnos.deleted_at')
            ->whereNull('inscripciones.deleted_at')
            ->select(
                'alumnos.id',
                DB::raw("CONCAT(alumnos.nombre, ' ', alumnos.apellido_paterno, ' ', alumnos.apellido_materno) as nombre_completo"),
                'alumnos.estado'
            )
            ->distinct() // Evita duplicados si un alumno está en varios periodos
            ->get();

        // Formatear el estado antes de enviar
        $alumnos->transform(function($alumno) {
            $alumno->estado = ($alumno->estado == 1) ? 'Inscrito' : 'Baja';
            return $alumno;
        });

        return response()->json($alumnos);
    }
}
