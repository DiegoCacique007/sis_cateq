<?php

namespace App\Http\Controllers\Catequista;

use App\Enums\CatequesisCapability as C;
use App\Http\Controllers\Controller;
use App\Http\Support\CatequesisHttp;
use App\Models\Secretaria\Evaluacion;
use App\Models\Secretaria\Rubro;
use App\Queries\AccessibleAsignaciones;
use App\Queries\AccessibleEvaluaciones;
use App\Queries\AccessibleInscripciones;
use App\Services\Authorization\CatequesisAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EvaluacionController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'asignacion_id' => ['nullable', 'integer', 'min:1'],
            'unidad_id' => ['nullable', 'regex:/^(final|[1-9][0-9]*)$/'],
        ]);
        $http = app(CatequesisHttp::class);
        $context = $http->context($request, C::ViewEvaluations);
        $unidadId = $request->input('unidad_id');
        $asignaciones = app(AccessibleAsignaciones::class)->for($context)
            ->with(['comunidad', 'grupo', 'nivel', 'periodo'])->get()->map(fn ($a) => (object) [
                'asignacion_id' => $a->id, 'grupo_id' => $a->grupo_id,
                'periodo_id' => $a->periodo_id, 'nivel_id' => $a->nivel_id,
                'comunidad' => $a->comunidad->comunidad, 'grupo' => $a->grupo->nombre,
                'nivel' => $a->nivel->nivel,
                'periodo' => $a->periodo->fecha_inicio->format('Y-m-d').' al '.$a->periodo->fecha_fin->format('Y-m-d'),
                'texto_asignacion' => $a->nivel->nivel.' - Grupo '.$a->grupo->nombre.' ('.$a->comunidad->comunidad.')',
            ]);
        $asignacionId = $request->integer('asignacion_id') ?: ($asignaciones->count() === 1 ? $asignaciones->sole()->asignacion_id : null);
        $asignacion = null;
        if ($asignacionId) {
            $http->enforce(app(CatequesisAccess::class)->canUseAsignacion($context, $asignacionId));
            $asignacion = $asignaciones->where('asignacion_id', $asignacionId)->sole();
        }
        $unidades = collect();
        $unidadSeleccionada = null;
        $rubros = collect();
        $alumnos = collect();
        $totalRubros = 0;

        if ($asignacion) {
            $unidades = \App\Models\Secretaria\Unidad::where('nivel_id', $asignacion->nivel_id)
                ->orderBy('numero')->get()->map(fn ($u) => (object) [
                    'id' => $u->id, 'numero' => $u->numero, 'nombre' => $u->nombre, 'text' => $u->unidad_texto,
                ]);

            if ($unidadId === 'final') {
                $unidadSeleccionada = (object) [
                    'id' => 'final',
                    'text' => 'Resumen Final de Nivel',
                ];

                $rubros = DB::table('rubros')->whereNull('deleted_at')->select('id', 'valor')->get();
                $totalRubros = (float) $rubros->sum('valor');

                $alumnosBase = app(AccessibleInscripciones::class)->for($context)
                    ->where('inscripciones.grupo_id', $asignacion->grupo_id)
                    ->with('alumno')->get()->map(fn ($i) => (object) [
                        'inscripcion_id' => $i->id, 'alumno_id' => $i->alumno_id,
                        'alumno_nombre' => $i->alumno->nombre_completo,
                    ])->sortBy('alumno_nombre')->values();

                $inscripcionesIds = $alumnosBase->pluck('inscripcion_id')->toArray();
                $unidadesIds = $unidades->pluck('id')->toArray();

                $evaluaciones = app(AccessibleEvaluaciones::class)->for($context)
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
                abort_if($unidadId && ! $unidadSeleccionada, 404);

                $rubros = DB::table('rubros')
                    ->whereNull('deleted_at')
                    ->select('id', 'nombre', 'valor')
                    ->orderBy('nombre')
                    ->get();

                $totalRubros = (float) $rubros->sum('valor');

                if ($unidadSeleccionada) {
                    $alumnosBase = app(AccessibleInscripciones::class)->for($context)
                        ->where('inscripciones.grupo_id', $asignacion->grupo_id)
                        ->with('alumno')->get()->map(fn ($i) => (object) [
                        'inscripcion_id' => $i->id, 'alumno_id' => $i->alumno_id,
                        'alumno_nombre' => $i->alumno->nombre_completo,
                    ])->sortBy('alumno_nombre')->values();

                    $inscripcionesIds = $alumnosBase->pluck('inscripcion_id')->toArray();

                    $evaluaciones = app(AccessibleEvaluaciones::class)->for($context)
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
            'asignacion_id' => ['required', 'integer', 'min:1'],
            'unidad_id' => ['required', 'integer', 'min:1'],
            'calificaciones' => ['required', 'array'],
            'calificaciones.*' => ['required', 'array', 'min:1'],
            'calificaciones.*.*' => ['nullable', 'numeric', 'min:0'],
        ]);
        DB::transaction(function () use ($request, $validated) {
            $http = app(CatequesisHttp::class);
            $context = $http->context($request, C::ManageEvaluations);
            $access = app(CatequesisAccess::class);
            $http->enforce($access->canUseAsignacion($context, $validated['asignacion_id']));
            $assignment = app(AccessibleAsignaciones::class)->for($context)->lockForUpdate()->findOrFail($validated['asignacion_id']);
            $rubros = Rubro::query()->get()->keyBy('id');
            foreach ($validated['calificaciones'] as $inscripcionId => $items) {
                abort_unless(ctype_digit((string) $inscripcionId) && (int) $inscripcionId > 0, 404);
                $http->enforce($access->canViewInscripcion($context, (int) $inscripcionId));
                app(AccessibleInscripciones::class)->for($context)->where('grupo_id', $assignment->grupo_id)
                    ->lockForUpdate()->findOrFail($inscripcionId);
                foreach ($items as $rubroId => $calificacion) {
                    abort_unless(ctype_digit((string) $rubroId) && (int) $rubroId > 0, 404);
                    $http->enforce($access->canCaptureEvaluacion($context, (int) $inscripcionId, (int) $validated['unidad_id'], (int) $rubroId));
                    $rubro = $rubros->get($rubroId);
                    if ($calificacion !== null && (float) $calificacion > (float) $rubro->valor) {
                        throw ValidationException::withMessages(['calificaciones' => 'La calificación no puede superar el valor máximo del rubro.']);
                    }
                    // Restauración limitada al destino autorizado; los demás scopes permanecen.
                    $matches = app(AccessibleEvaluaciones::class)->for($context)->withTrashed()
                        ->where('inscripcion_id', $inscripcionId)->where('unidad_id', $validated['unidad_id'])
                        ->where('rubro_id', $rubroId)->lockForUpdate()->get();
                    abort_if($matches->count() > 1, 409, 'Existen evaluaciones duplicadas. Solicita revisión a Secretaría.');
                    $evaluacion = $matches->isEmpty() ? null : $matches->sole();
                    if ($calificacion === null || $calificacion === '') {
                        if ($evaluacion && ! $evaluacion->trashed()) {
                            $evaluacion->delete();
                        }

                        continue;
                    }
                    if ($evaluacion) {
                        if ($evaluacion->trashed()) {
                            $evaluacion->restore();
                        }
                        $evaluacion->update(['calificacion' => $calificacion, 'periodo_id' => $context->activePeriodId]);
                    } else {
                        Evaluacion::create([
                            'inscripcion_id' => $inscripcionId, 'unidad_id' => $validated['unidad_id'],
                            'rubro_id' => $rubroId, 'calificacion' => $calificacion, 'periodo_id' => $context->activePeriodId,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('catequista.evaluaciones.index', [
            'asignacion_id' => $validated['asignacion_id'], 'unidad_id' => $validated['unidad_id'],
        ])->with('success', 'Calificaciones guardadas correctamente.');
    }
}
