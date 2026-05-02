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
        $rubroId = $request->input('rubro_id');

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

        $contextoCompleto = $periodoId && $grupoId && $unidadId && $rubroId;

        if ($contextoCompleto) {
            $alumnos = DB::table('inscripciones')
                ->join('alumnos', 'inscripciones.alumno_id', '=', 'alumnos.id')
                ->leftJoin('evaluaciones', function ($join) use ($unidadId, $rubroId) {
                    $join->on('evaluaciones.inscripcion_id', '=', 'inscripciones.id')
                        ->where('evaluaciones.unidad_id', '=', $unidadId)
                        ->where('evaluaciones.rubro_id', '=', $rubroId)
                        ->whereNull('evaluaciones.deleted_at');
                })
                ->where('inscripciones.periodo_id', $periodoId)
                ->where('inscripciones.grupo_id', $grupoId)
                ->whereNull('inscripciones.deleted_at')
                ->whereNull('alumnos.deleted_at')
                ->select(
                    'inscripciones.id as inscripcion_id',
                    'alumnos.id as alumno_id',
                    DB::raw("TRIM(CONCAT(alumnos.nombre, ' ', alumnos.apellido_paterno, ' ', COALESCE(alumnos.apellido_materno, ''))) as alumno_nombre"),
                    'evaluaciones.id as evaluacion_id',
                    'evaluaciones.calificacion'
                )
                ->orderBy('alumnos.nombre')
                ->orderBy('alumnos.apellido_paterno')
                ->get();
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
            'rubroId',
            'contextoCompleto'
        ));
    }

    public function guardarMasivo(Request $request)
    {
        $validated = $request->validate([
            'grupo_id' => ['required', 'exists:grupos,id'],
            'unidad_id' => ['required', 'exists:unidades,id'],
            'rubro_id' => ['required', 'exists:rubros,id'],
            'calificaciones' => ['required', 'array'],
            'calificaciones.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'grupo_id.required' => 'Selecciona un grupo.',
            'grupo_id.exists' => 'El grupo seleccionado no existe.',
            'unidad_id.required' => 'Selecciona una unidad.',
            'unidad_id.exists' => 'La unidad seleccionada no existe.',
            'rubro_id.required' => 'Selecciona un rubro.',
            'rubro_id.exists' => 'El rubro seleccionado no existe.',
            'calificaciones.required' => 'No se recibieron calificaciones para guardar.',
            'calificaciones.array' => 'El formato de calificaciones no es válido.',
            'calificaciones.*.numeric' => 'Cada calificación debe ser numérica.',
            'calificaciones.*.min' => 'La calificación no puede ser menor a 0.',
            'calificaciones.*.max' => 'La calificación no puede ser mayor a 100.',
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

        foreach ($validated['calificaciones'] as $inscripcionId => $calificacion) {
            if (!in_array((string) $inscripcionId, $inscripcionesValidas, true)) {
                continue;
            }

            $evaluacion = Evaluacion::withTrashed()
                ->where('inscripcion_id', $inscripcionId)
                ->where('unidad_id', $validated['unidad_id'])
                ->where('rubro_id', $validated['rubro_id'])
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
                    'rubro_id' => $validated['rubro_id'],
                    'calificacion' => $calificacion,
                    'periodo_id' => $validated['periodo_id'],
                ]);
            }

            $guardadas++;
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
                'rubro_id' => $validated['rubro_id'],
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
