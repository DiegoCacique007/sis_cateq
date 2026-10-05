<?php

namespace App\Http\Controllers\CoordComunidad;

use App\Enums\CatequesisCapability as C;
use App\Http\Controllers\Controller;
use App\Http\Support\CatequesisHttp;
use App\Models\Secretaria\Periodo;
use App\Models\Secretaria\Rubro;
use App\Models\Secretaria\Unidad;
use App\Queries\AccessibleAlumnos;
use App\Queries\AccessibleAsignaciones;
use App\Queries\AccessibleCatequistas;
use App\Queries\AccessibleComunidades;
use App\Queries\AccessibleEvaluaciones;
use App\Queries\AccessibleInscripciones;
use App\Services\Authorization\CatequesisAccess;
use Illuminate\Http\Request;

class CoordComunidadController extends Controller
{
    public function index(Request $request)
    {
        $context = app(CatequesisHttp::class)->context($request, C::ViewGroupStudents);
        $totalComunidades = app(AccessibleComunidades::class)->for($context)->count();
        $totalAlumnos = app(AccessibleAlumnos::class)->for($context)->count();
        $totalGrupos = app(AccessibleAsignaciones::class)->for($context)->count();
        $totalCatequistas = app(AccessibleCatequistas::class)->for($context)->count();
        $totalEvaluaciones = app(AccessibleEvaluaciones::class)->for($context)->count();
        $totalInscripciones = app(AccessibleInscripciones::class)->for($context)->where('estado', 1)->count();

        return view('CoordComunidad.dashboard', compact('totalComunidades', 'totalAlumnos', 'totalGrupos', 'totalCatequistas', 'totalEvaluaciones', 'totalInscripciones'));
    }

    public function catequistas(Request $request)
    {
        $context = app(CatequesisHttp::class)->context($request, C::ViewCatechists);
        $registros = app(AccessibleCatequistas::class)->for($context)->orderBy('name')->paginate(25);

        return view('CoordComunidad.catequistas', compact('registros'));
    }

    public function evaluaciones(Request $request)
    {
        $request->validate([
            'sacramento' => ['nullable', 'string', 'max:255'],
            'nivel_id' => ['nullable', 'integer', 'min:1'],
            'asignacion_id' => ['nullable', 'integer', 'min:1'],
            'unidad_id' => ['nullable', 'integer', 'min:1'],
        ]);
        $http = app(CatequesisHttp::class);
        $context = $http->context($request, C::ViewEvaluations);
        $periodoId = $context->activePeriodId;
        $periodoActivo = Periodo::findOrFail($periodoId);
        $error_periodo = false;
        if ($request->filled('asignacion_id')) {
            $http->enforce(app(CatequesisAccess::class)->canUseAsignacion($context, $request->integer('asignacion_id')));
        }
        $periodoTexto = $periodoActivo
            ? ($periodoActivo->fecha_inicio ? \Carbon\Carbon::parse($periodoActivo->fecha_inicio)->format('Y') : '').' al '.($periodoActivo->fecha_fin ? \Carbon\Carbon::parse($periodoActivo->fecha_fin)->format('Y') : '')
            : 'Periodo no encontrado';

        // 2. Filtros del Request
        $sacramento = $request->input('sacramento');
        $nivelId = $request->input('nivel_id');
        $asignacionId = $request->input('asignacion_id');
        $unidadId = $request->input('unidad_id');

        // 3. Niveles disponibles (dependen de sacramento)
        $niveles = collect();
        if ($sacramento) {
            $niveles = \App\Models\Secretaria\Nivel::whereIn('id', app(AccessibleAsignaciones::class)->for($context)->select('nivel_id'))->where('sacramento', $sacramento)->orderBy('numero')->get();
        }

        // 4. Asignaciones disponibles según periodo, sacramento y nivel
        $asignacionesQuery = app(AccessibleAsignaciones::class)->for($context)->with(['comunidad', 'grupo', 'nivel', 'catequista'])
            ->where('periodo_id', $periodoId)
            ->whereNull('deleted_at');

        if ($sacramento) {
            $asignacionesQuery->whereHas('nivel', function ($q) use ($sacramento) {
                $q->where('sacramento', $sacramento);
            });
        }

        if ($nivelId) {
            $asignacionesQuery->where('nivel_id', $nivelId);
        }

        $asignaciones = $asignacionesQuery->get()->map(function ($asig) {
            $comunidad = $asig->comunidad->comunidad ?? 'Sin Comunidad';
            $grupo = $asig->grupo->nombre ?? 'Sin Grupo';
            $nivel = $asig->nivel->nivel ?? 'Sin Nivel';
            $catequista = $asig->catequista->name ?? 'Sin Catequista';

            // Format: Comunidad - Grupo - Sacramento Nivel - Catequista
            $asig->nombre_completo = "{$comunidad} - {$grupo} - {$nivel} - {$catequista}";

            return $asig;
        })->sortBy('nombre_completo');

        if ($asignacionId) {
            abort_unless($asignaciones->contains('id', (int) $asignacionId), 404);
        }
        if ($nivelId) {
            abort_unless(app(AccessibleAsignaciones::class)->for($context)->where('nivel_id', $nivelId)->exists(), 404);
        }
        if ($unidadId && $asignacionId) {
            $selected = $asignaciones->where('id', (int) $asignacionId)->sole();
            abort_unless(Unidad::whereKey($unidadId)->where('nivel_id', $selected->nivel_id)->exists(), 404);
        }
        // 5. Unidades (solo si hay nivel seleccionado)
        $unidades = collect();
        if ($nivelId) {
            $unidades = Unidad::where('nivel_id', $nivelId)->orderBy('numero')->get();
        } elseif ($asignacionId) {
            // Fallback: si por alguna razón tiene asignación pero no nivel en el request
            $asignacionSeleccionada = $asignaciones->where('id', (int) $asignacionId)->sole();
            if ($asignacionSeleccionada) {
                $unidades = Unidad::where('nivel_id', $asignacionSeleccionada->nivel_id)
                    ->orderBy('numero')
                    ->get();
            }
        }

        $rubros = Rubro::orderBy('id')->get();
        $totalRubros = (float) $rubros->sum('valor');

        // 6. Alumnos (solo si todo está seleccionado)
        $alumnos = collect();
        $calificacionesMap = [];
        $promedios = [];

        if ($sacramento && $nivelId && $asignacionId && $unidadId) {
            $asignacionSel = $asignaciones->where('id', (int) $asignacionId)->sole();
            $grupoId = $asignacionSel ? $asignacionSel->grupo_id : null;

            if ($grupoId) {
                $inscripciones = app(AccessibleInscripciones::class)->for($context)->with(['alumno', 'grupo'])
                    ->where('grupo_id', $grupoId)
                    ->where(function ($q) {
                        $q->where('estado', 1)->orWhereNull('estado');
                    })
                    ->whereNull('deleted_at')
                    ->get();

                $inscripcionesIds = $inscripciones->pluck('id')->toArray();

                $evaluaciones = app(AccessibleEvaluaciones::class)->for($context)->whereIn('inscripcion_id', $inscripcionesIds)
                    ->where('unidad_id', $unidadId)
                    ->get();

                foreach ($evaluaciones as $eval) {
                    $calificacionesMap[$eval->inscripcion_id][$eval->rubro_id] = $eval->calificacion;
                }

                foreach ($inscripciones as $inscripcion) {
                    $inscripcionId = $inscripcion->id;
                    $alumnoCalifs = $calificacionesMap[$inscripcionId] ?? [];

                    if (count($alumnoCalifs) > 0 && $totalRubros > 0) {
                        $suma = array_sum($alumnoCalifs);
                        $promedio = ($suma / $totalRubros) * 10;
                        $promedios[$inscripcionId] = round($promedio, 1);
                    } else {
                        $promedios[$inscripcionId] = null;
                    }
                }

                $alumnos = $inscripciones->sortBy(function ($inscripcion) {
                    return $inscripcion->alumno->nombre.' '.$inscripcion->alumno->apellido_paterno;
                });
            }
        }

        return view('CoordComunidad.evaluaciones', compact(
            'error_periodo',
            'periodoTexto',
            'sacramento',
            'nivelId',
            'asignacionId',
            'unidadId',
            'niveles',
            'asignaciones',
            'unidades',
            'rubros',
            'alumnos',
            'calificacionesMap',
            'promedios'
        ));
    }
}
