<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use App\Models\Secretaria\Evaluacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvaluacionController extends Controller
{
    public function index(Request $request)
    {
        $periodoId = session('periodo_activo_id');
        $grupoId = $request->input('grupo_id');
        $unidadId = $request->input('unidad_id');

        $periodos = DB::table('periodos')
            ->whereNull('deleted_at')
            ->select(
                'id',
                'fecha_inicio',
                'fecha_fin',
                DB::raw("CONCAT(fecha_inicio, ' al ', fecha_fin) as text")
            )
            ->orderBy('fecha_inicio', 'desc')
            ->get();

        $grupos = DB::table('grupos')
            ->whereNull('deleted_at')
            ->select('id', 'nombre')
            ->orderBy('nombre')
            ->get();

        $unidades = DB::table('unidades')
            ->whereNull('deleted_at')
            ->select(
                'id',
                'numero',
                'nombre',
                DB::raw("CONCAT('Unidad ', numero, ' - ', nombre) as text")
            )
            ->orderBy('numero')
            ->get();

        $rubros = DB::table('rubros')
            ->whereNull('deleted_at')
            ->select('id', 'nombre', 'valor')
            ->orderBy('nombre')
            ->get();

        $alumnos = collect();
        $calificacionesMap = [];

        $contextoCompleto = $periodoId && $grupoId && $unidadId;

        if ($contextoCompleto) {
            $alumnos = DB::table('inscripciones')
                ->join('alumnos', 'inscripciones.alumno_id', '=', 'alumnos.id')
                ->where('inscripciones.periodo_id', $periodoId)
                ->where('inscripciones.grupo_id', $grupoId)
                ->whereNull('inscripciones.deleted_at')
                ->whereNull('alumnos.deleted_at')
                ->select(
                    'inscripciones.id as inscripcion_id',
                    'alumnos.id as alumno_id',
                    DB::raw("TRIM(CONCAT(alumnos.nombre, ' ', alumnos.apellido_paterno, ' ', COALESCE(alumnos.apellido_materno, ''))) as alumno_nombre")
                )
                ->orderBy('alumnos.nombre')
                ->orderBy('alumnos.apellido_paterno')
                ->get();

            $inscripcionIds = $alumnos->pluck('inscripcion_id')->toArray();

            if (!empty($inscripcionIds)) {
                $evaluacionesRaw = DB::table('evaluaciones')
                    ->whereIn('inscripcion_id', $inscripcionIds)
                    ->where('unidad_id', $unidadId)
                    ->whereNull('deleted_at')
                    ->get();

                foreach ($evaluacionesRaw as $ev) {
                    $calificacionesMap[$ev->inscripcion_id][$ev->rubro_id] = $ev;
                }
            }
        }

        return view('secretaria.evaluaciones.index', compact(
            'periodos',
            'grupos',
            'unidades',
            'rubros',
            'alumnos',
            'periodoId',
            'grupoId',
            'unidadId',
            'contextoCompleto',
            'calificacionesMap'
        ));
    }

    public function guardarMasivo(Request $request)
    {
        $validated = $request->validate([
            'grupo_id' => ['required', 'exists:grupos,id'],
            'unidad_id' => ['required', 'exists:unidades,id'],
            'calificaciones' => ['required', 'array'],
            'calificaciones.*.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'grupo_id.required' => 'Selecciona un grupo.',
            'grupo_id.exists' => 'El grupo seleccionado no existe.',
            'unidad_id.required' => 'Selecciona una unidad.',
            'unidad_id.exists' => 'La unidad seleccionada no existe.',
            'calificaciones.required' => 'No se recibieron calificaciones para guardar.',
            'calificaciones.array' => 'El formato de calificaciones no es válido.',
            'calificaciones.*.*.numeric' => 'Cada calificación debe ser numérica.',
            'calificaciones.*.*.min' => 'La calificación no puede ser menor a 0.',
            'calificaciones.*.*.max' => 'La calificación no puede ser mayor a 100.',
        ]);

        $validated['periodo_id'] = session('periodo_activo_id');

        $inscripcionesValidas = DB::table('inscripciones')
            ->where('periodo_id', $validated['periodo_id'])
            ->where('grupo_id', $validated['grupo_id'])
            ->whereNull('deleted_at')
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->toArray();

        $guardadas = 0;
        $eliminadas = 0;

        foreach ($validated['calificaciones'] as $inscripcionId => $rubrosData) {
            if (!in_array((string) $inscripcionId, $inscripcionesValidas, true)) {
                continue;
            }

            foreach ($rubrosData as $rubroId => $calificacion) {
                $evaluacion = Evaluacion::withTrashed()
                    ->where('inscripcion_id', $inscripcionId)
                    ->where('unidad_id', $validated['unidad_id'])
                    ->where('rubro_id', $rubroId)
                    ->first();

                if ($calificacion === null || $calificacion === '') {
                    if ($evaluacion && !$evaluacion->trashed()) {
                        $evaluacion->delete();
                        $eliminadas++;
                    }
                    continue;
                }

                if ($evaluacion) {
                    if ($evaluacion->trashed()) {
                        $evaluacion->restore();
                    }
                    $evaluacion->update([
                        'calificacion' => $calificacion,
                    ]);
                } else {
                    Evaluacion::create([
                        'inscripcion_id' => $inscripcionId,
                        'unidad_id' => $validated['unidad_id'],
                        'rubro_id' => $rubroId,
                        'calificacion' => $calificacion,
                        'periodo_id' => $validated['periodo_id'],
                    ]);
                }

                $guardadas++;
            }
        }

        $mensaje = "Evaluaciones guardadas correctamente. Registros actualizados: {$guardadas}.";

        if ($eliminadas > 0) {
            $mensaje .= " Calificaciones eliminadas: {$eliminadas}.";
        }

        return redirect()
            ->route('secretaria.evaluaciones.index', [
                'periodo_id' => $validated['periodo_id'],
                'grupo_id' => $validated['grupo_id'],
                'unidad_id' => $validated['unidad_id'],
            ])
            ->with('success', $mensaje);
    }

    public function destroy($id)
    {
        $evaluacion = Evaluacion::findOrFail($id);
        $evaluacion->delete();

        return redirect()
            ->back()
            ->with('success', 'Evaluación eliminada correctamente.');
    }
}
