<?php

namespace App\Http\Controllers\Parroco;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB; // <-- ¡ESTA ES LA LÍNEA MÁGICA QUE FALTABA!

class ParrocoController extends Controller
{
    // Asegúrate de que tu ruta en web.php apunte a este método: 'indexEvaluaciones'
    public function indexEvaluaciones() {
        try {
            $evaluaciones = DB::table('evaluaciones')
                ->join('inscripciones', 'evaluaciones.inscripcion_id', '=', 'inscripciones.id')
                ->join('alumnos', 'inscripciones.alumno_id', '=', 'alumnos.id')
                ->join('rubros', 'evaluaciones.rubro_id', '=', 'rubros.id')
                ->join('unidades', 'evaluaciones.unidad_id', '=', 'unidades.id')

                // --- SIN CANDADO: El Párroco ve a todos los alumnos de la base de datos ---

                ->whereNull('evaluaciones.deleted_at')
                ->select(
                    'evaluaciones.id',
                    'evaluaciones.inscripcion_id',
                    'evaluaciones.unidad_id',
                    'evaluaciones.rubro_id',
                    'evaluaciones.calificacion',
                    DB::raw("CONCAT(alumnos.nombre, ' ', alumnos.apellido_paterno) as inscripcion_nombre"),
                    'unidades.nombre as unidad_nombre',
                    'rubros.nombre as rubro_nombre'
                )
                ->get();

            return response()->json($evaluaciones);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
