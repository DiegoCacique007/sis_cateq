<?php

namespace App\Http\Controllers\Catequista;

use App\Http\Controllers\Controller;
use App\Models\Secretaria\Evaluacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvaluacionController extends Controller
{
    public function index(Request $request)
    {
        $catequistaId = auth()->id();
        $unidadId = $request->input('unidad_id');

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
                'asigna_grupo.nivel_id',
                'comunidades.comunidad',
                'grupos.nombre as grupo',
                'niveles.nivel',
                DB::raw("CONCAT(periodos.fecha_inicio, ' al ', periodos.fecha_fin) as periodo"),
                DB::raw("CONCAT(niveles.nivel, ' - Grupo ', grupos.nombre, ' (', comunidades.comunidad, ')') as texto_asignacion")
            );

        $asignaciones = $asignacionesQuery->get();
        $asignacionId = $request->input('asignacion_id');

        if ($asignacionId) {
            $asignacion = $asignaciones->firstWhere('asignacion_id', (int) $asignacionId);
        } else {
            $asignacion = $asignaciones->first();
            $asignacionId = $asignacion ? $asignacion->asignacion_id : null;
        }

        $unidades = collect();
        $unidadSeleccionada = null;
        $rubros = collect();
        $alumnos = collect();
        $totalRubros = 0;

        if ($asignacion) {
            $unidades = DB::table('unidades')
                ->where('nivel_id', $asignacion->nivel_id)
                ->whereNull('deleted_at')
                ->select(
                    'id',
                    'numero',
                    'nombre',
                    DB::raw("CONCAT('Unidad ', numero, ' - ', nombre) as text")
                )
                ->orderBy('numero')
                ->get();

            if ($unidadId === 'final') {
                $unidadSeleccionada = (object)[
                    'id' => 'final',
                    'text' => 'Resumen Final de Nivel'
                ];

                $rubros = DB::table('rubros')->whereNull('deleted_at')->select('id', 'valor')->get();
                $totalRubros = (float) $rubros->sum('valor');

                $alumnosBase = DB::table('inscripciones')
                    ->join('alumnos', 'inscripciones.alumno_id', '=', 'alumnos.id')
                    ->where('inscripciones.grupo_id', $asignacion->grupo_id)
                    ->where('inscripciones.periodo_id', $asignacion->periodo_id)
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

                $inscripcionesIds = $alumnosBase->pluck('inscripcion_id')->toArray();
                $unidadesIds = $unidades->pluck('id')->toArray();

                $evaluaciones = DB::table('evaluaciones')
                    ->whereIn('inscripcion_id', $inscripcionesIds)
                    ->whereIn('unidad_id', $unidadesIds)
                    ->whereNull('deleted_at')
                    ->select('inscripcion_id', 'unidad_id', 'calificacion')
                    ->get();

                $alumnos = $alumnosBase->map(function ($alumno) use ($evaluaciones, $unidades, $totalRubros) {
                    $evs = $evaluaciones->where('inscripcion_id', $alumno->inscripcion_id);
                    $promediosUnidad = [];
                    $sumaPromedios = 0;
                    $unidadesEvaluadas = 0;

                    foreach ($unidades as $unidad) {
                        $evsUnidad = $evs->where('unidad_id', $unidad->id);
                        if ($evsUnidad->count() > 0 && $totalRubros > 0) {
                            $sumaCalificaciones = $evsUnidad->sum('calificacion');
                            $promedioUnidad = ($sumaCalificaciones / $totalRubros) * 10;
                            $promediosUnidad[$unidad->id] = round($promedioUnidad, 2);
                            $sumaPromedios += $promedioUnidad;
                            $unidadesEvaluadas++;
                        } else {
                            $promediosUnidad[$unidad->id] = null;
                        }
                    }

                    $alumno->promedios_unidad = $promediosUnidad;
                    $alumno->promedio_final = $unidadesEvaluadas > 0 ? round($sumaPromedios / $unidadesEvaluadas, 2) : null;

                    return $alumno;
                });
            } else {
                $unidadSeleccionada = $unidades->firstWhere('id', (int) $unidadId);

                $rubros = DB::table('rubros')
                    ->whereNull('deleted_at')
                    ->select('id', 'nombre', 'valor')
                    ->orderBy('nombre')
                    ->get();

                $totalRubros = (float) $rubros->sum('valor');

                if ($unidadSeleccionada) {
                    $alumnosBase = DB::table('inscripciones')
                        ->join('alumnos', 'inscripciones.alumno_id', '=', 'alumnos.id')
                        ->where('inscripciones.grupo_id', $asignacion->grupo_id)
                        ->where('inscripciones.periodo_id', $asignacion->periodo_id)
                        ->whereNull('inscripciones.deleted_at')
                        ->whereNull('alumnos.deleted_at')
                        ->select(
                            'inscripciones.id as inscripcion_id',
                            'alumnos.id as alumno_id',
                            DB::raw("TRIM(CONCAT(alumnos.nombre, ' ', alumnos.apellido_paterno, ' ', COALESCE(alumnos.apellido_materno, ''))) as alumno_nombre")
                        )
                        ->groupBy(
                            'inscripciones.id',
                            'alumnos.id',
                            'alumnos.nombre',
                            'alumnos.apellido_paterno',
                            'alumnos.apellido_materno'
                        )
                        ->orderBy('alumnos.nombre')
                        ->orderBy('alumnos.apellido_paterno')
                        ->get();

                    $inscripcionesIds = $alumnosBase->pluck('inscripcion_id')->toArray();

                    $evaluaciones = DB::table('evaluaciones')
                        ->whereIn('inscripcion_id', $inscripcionesIds)
                        ->where('unidad_id', $unidadSeleccionada->id)
                        ->whereNull('deleted_at')
                        ->select('id', 'inscripcion_id', 'rubro_id', 'calificacion')
                        ->get()
                        ->groupBy('inscripcion_id');

                    $alumnos = $alumnosBase->map(function ($alumno) use ($evaluaciones, $rubros, $totalRubros) {
                        $evaluacionesAlumno = $evaluaciones
                            ->get($alumno->inscripcion_id, collect())
                            ->keyBy('rubro_id');

                        $calificaciones = [];
                        $puntos = 0;
                        $capturados = 0;

                        foreach ($rubros as $rubro) {
                            $evaluacion = $evaluacionesAlumno->get($rubro->id);
                            $calificacion = $evaluacion ? (float) $evaluacion->calificacion : null;
                            $aporte = null;

                            if ($calificacion !== null) {
                                $aporte = $calificacion;
                                $puntos += $aporte;
                                $capturados++;
                            }

                            $calificaciones[$rubro->id] = [
                                'calificacion' => $calificacion,
                                'aporte' => $aporte,
                            ];
                        }

                        $promedio = $totalRubros > 0 ? ($puntos / $totalRubros) * 10 : 0;

                        $alumno->calificaciones = $calificaciones;
                        $alumno->puntos = round($puntos, 2);
                        $alumno->promedio = $capturados > 0 ? round($promedio, 2) : null;
                        $alumno->capturados = $capturados;
                        $alumno->total_rubros = $rubros->count();

                        return $alumno;
                    });
                }
            }
        }

        return view('catequista.evaluaciones.index', compact(
            'asignaciones',
            'asignacionId',
            'asignacion',
            'unidades',
            'unidadId',
            'unidadSeleccionada',
            'rubros',
            'totalRubros',
            'alumnos'
        ));
    }

    public function guardar(Request $request)
    {
        $validated = $request->validate([
            'asignacion_id' => ['required', 'integer'],
            'unidad_id' => ['required', 'exists:unidades,id'],
            'calificaciones' => ['required', 'array'],
            'calificaciones.*' => ['array'],
            'calificaciones.*.*' => ['nullable', 'numeric', 'min:0'],
        ], [
            'calificaciones.*.*.min' => 'La calificación no puede ser menor a 0. No se permiten valores negativos.',
            'calificaciones.*.*.numeric' => 'La calificación debe ser un valor numérico.',
        ]);

        $rubrosValores = DB::table('rubros')
            ->whereNull('deleted_at')
            ->pluck('valor', 'id');

        foreach ($validated['calificaciones'] as $rubrosAlumno) {
            foreach ($rubrosAlumno as $rubroId => $calificacion) {
                if ($calificacion !== null && $calificacion !== '') {
                    $valorMaximo = (float) ($rubrosValores[$rubroId] ?? 0);

                    if ((float) $calificacion > $valorMaximo) {
                        return back()
                            ->withInput()
                            ->withErrors([
                                'calificaciones' => "Una calificación supera el valor máximo permitido del rubro. El valor máximo permitido para ese rubro es de {$valorMaximo}.",
                            ]);
                    }
                }
            }
        }

        $catequistaId = auth()->id();

        $asignacion = DB::table('asigna_grupo')
            ->where('id', $validated['asignacion_id'])
            ->where('catequista_id', $catequistaId)
            ->where('periodo_id', session('periodo_activo_id'))
            ->whereNull('deleted_at')
            ->first();

        if (!$asignacion) {
            return redirect()
                ->route('catequista.evaluaciones.index')
                ->withErrors(['grupo' => 'No tienes un grupo asignado.']);
        }

        $unidadValida = DB::table('unidades')
            ->where('id', $validated['unidad_id'])
            ->where('nivel_id', $asignacion->nivel_id)
            ->whereNull('deleted_at')
            ->exists();

        if (!$unidadValida) {
            return redirect()
                ->route('catequista.evaluaciones.index')
                ->withErrors(['unidad_id' => 'La unidad seleccionada no pertenece a tu nivel asignado.']);
        }

        $inscripcionesValidas = DB::table('inscripciones')
            ->where('grupo_id', $asignacion->grupo_id)
            ->where('periodo_id', $asignacion->periodo_id)
            ->whereNull('deleted_at')
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->toArray();

        $rubrosValidos = DB::table('rubros')
            ->whereNull('deleted_at')
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->toArray();

        $guardadas = 0;
        $eliminadas = 0;

        DB::transaction(function () use ($validated, $inscripcionesValidas, $rubrosValidos, &$guardadas, &$eliminadas) {
            foreach ($validated['calificaciones'] as $inscripcionId => $rubrosAlumno) {
                if (!in_array((string) $inscripcionId, $inscripcionesValidas, true)) {
                    continue;
                }

                foreach ($rubrosAlumno as $rubroId => $calificacion) {
                    if (!in_array((string) $rubroId, $rubrosValidos, true)) {
                        continue;
                    }

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
                        ]);
                    }

                    $guardadas++;
                }
            }
        });

        return redirect()
            ->route('catequista.evaluaciones.index', [
                'asignacion_id' => $validated['asignacion_id'],
                'unidad_id' => $validated['unidad_id'],
            ])
            ->with('success', "Calificaciones guardadas correctamente. Registros actualizados: {$guardadas}.");
    }
}
