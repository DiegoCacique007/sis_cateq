<?php
namespace App\Http\Controllers\Catequista;

use App\Http\Controllers\Controller;
use App\Models\Catequista\Evaluacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvaluacionController extends Controller {

    public function index() {
        // 1. Identificamos quién es el usuario logueado y qué rol tiene
        $usuario = auth()->user();

        // 2. Preparamos la consulta base (sin candados)
        $query = DB::table('evaluaciones')
            ->join('inscripciones', 'evaluaciones.inscripcion_id', '=', 'inscripciones.id')
            ->join('alumnos', 'inscripciones.alumno_id', '=', 'alumnos.id')
            ->join('rubros', 'evaluaciones.rubro_id', '=', 'rubros.id')
            ->join('unidades', 'evaluaciones.unidad_id', '=', 'unidades.id')
            ->whereNull('evaluaciones.deleted_at');

        // 3. LA MAGIA: Si es catequista, le ponemos el candado. Si es Párroco, pasa de largo.
        if ($usuario->role === 'catequista') {
            $query->join('asigna_grupo', 'inscripciones.grupo_id', '=', 'asigna_grupo.grupo_id')
                ->where('asigna_grupo.catequista_id', $usuario->id);
        }

        // 4. Seleccionamos los datos (IDs ocultos y Nombres formateados)
        $evaluaciones = $query->select(
            'evaluaciones.id',
            'evaluaciones.inscripcion_id',
            'evaluaciones.unidad_id',
            'evaluaciones.rubro_id',
            'evaluaciones.calificacion',

            // --- AQUÍ ESTÁ LA MAGIA: Agregamos COALESCE(apellido_materno) ---
            DB::raw("CONCAT(alumnos.nombre, ' ', alumnos.apellido_paterno, ' ', COALESCE(alumnos.apellido_materno, '')) as inscripcion_nombre"),

            'unidades.nombre as unidad_nombre',
            'rubros.nombre as rubro_nombre'
        )->get();

        return response()->json($evaluaciones);
    }

    public function store(Request $request) {
        Evaluacion::create($request->all());
        return response()->json(['success' => true]);
    }

    public function update(Request $request, $id) {
        $evaluacion = Evaluacion::findOrFail($id);
        $evaluacion->update($request->all());
        return response()->json(['success' => true]);
    }

    public function destroy($id) {
        $evaluacion = Evaluacion::findOrFail($id);
        $evaluacion->delete();
        return response()->json(['success' => true]);
    }
}
