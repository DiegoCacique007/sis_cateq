<?php

namespace App\Http\Controllers\CoordComunidad;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use App\Models\Secretaria\AsignaGrupo;
use App\Models\Secretaria\Periodo;
use App\Models\Secretaria\Unidad;
use App\Models\Secretaria\Inscripcion;
use App\Models\Secretaria\Evaluacion;
use App\Models\Secretaria\Rubro;

class CoordComunidadController extends Controller
{
    public function index()
    {
        $periodoActivoId = session('periodo_activo_id');

        $totalComunidades = DB::table('comunidades')
            ->whereNull('deleted_at')
            ->count();

        $totalAlumnos = DB::table('alumnos')
            ->whereNull('deleted_at')
            ->count();

        $totalGrupos = DB::table('asigna_grupo')
            ->whereNull('deleted_at')
            ->when($periodoActivoId, function ($q) use ($periodoActivoId) {
                $q->where('periodo_id', $periodoActivoId);
            })
            ->count();

        $totalCatequistas = DB::table('users')
            ->where('role', 'catequista')
            ->where('status', 'aprobado')
            ->count();

        $totalEvaluaciones = DB::table('evaluaciones')
            ->whereNull('deleted_at')
            ->when($periodoActivoId, function ($q) use ($periodoActivoId) {
                $q->where('periodo_id', $periodoActivoId);
            })
            ->count();

        $totalInscripciones = DB::table('inscripciones')
            ->whereNull('deleted_at')
            ->where('estado', 1)
            ->when($periodoActivoId, function ($q) use ($periodoActivoId) {
                $q->where('periodo_id', $periodoActivoId);
            })
            ->count();

        return view('CoordComunidad.dashboard', compact(
            'totalComunidades',
            'totalAlumnos',
            'totalGrupos',
            'totalCatequistas',
            'totalEvaluaciones',
            'totalInscripciones'
        ));
    }

    public function catequistas()
    {
        $registros = DB::table('users')
            ->where('role', 'catequista')
            ->where('status', 'aprobado')
            ->orderBy('name')
            ->paginate(25);

        return view('CoordComunidad.catequistas', compact('registros'));
    }

    public function evaluaciones(Request $request)
    {
        if (!in_array(auth()->user()->role, ['admin', 'coordinador_comunidades'])) {
            abort(403);
        }

        // 1. Periodo (desde sesión)
        $periodoId = session('periodo_activo_id');
        $periodoNombre = session('periodo_activo_nombre');
        $periodoActivo = Periodo::find($periodoId);
        $error_periodo = false;

        // Si no hay periodo activo, mandar a la vista sin datos
        if (!$periodoId) {
            return view('CoordComunidad.evaluaciones', [
                'error_periodo' => true,
                'periodoTexto' => null,
                'sacramento' => null,
                'nivelId' => null,
                'asignacionId' => null,
                'unidadId' => null,
                'niveles' => collect(),
                'asignaciones' => collect(),
                'unidades' => collect(),
                'rubros' => collect(),
                'alumnos' => collect(),
                'calificacionesMap' => [],
                'promedios' => []
            ]);
        }

        $periodoTexto = $periodoActivo 
            ? ($periodoActivo->fecha_inicio ? \Carbon\Carbon::parse($periodoActivo->fecha_inicio)->format('Y') : '') . ' al ' . ($periodoActivo->fecha_fin ? \Carbon\Carbon::parse($periodoActivo->fecha_fin)->format('Y') : '')
            : 'Periodo no encontrado';

        // 2. Filtros del Request
        $sacramento = $request->input('sacramento');
        $nivelId = $request->input('nivel_id');
        $asignacionId = $request->input('asignacion_id');
        $unidadId = $request->input('unidad_id');

        // 3. Niveles disponibles (dependen de sacramento)
        $niveles = collect();
        if ($sacramento) {
            $niveles = \App\Models\Secretaria\Nivel::where('sacramento', $sacramento)->orderBy('numero')->get();
        }

        // 4. Asignaciones disponibles según periodo, sacramento y nivel
        $asignacionesQuery = AsignaGrupo::with(['comunidad', 'grupo', 'nivel', 'catequista'])
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

        // 5. Unidades (solo si hay nivel seleccionado)
        $unidades = collect();
        if ($nivelId) {
            $unidades = Unidad::where('nivel_id', $nivelId)->orderBy('numero')->get();
        } elseif ($asignacionId) {
            // Fallback: si por alguna razón tiene asignación pero no nivel en el request
            $asignacionSeleccionada = $asignaciones->firstWhere('id', (int) $asignacionId);
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
            $asignacionSel = $asignaciones->firstWhere('id', (int) $asignacionId);
            $grupoId = $asignacionSel ? $asignacionSel->grupo_id : null;

            if ($grupoId) {
                $inscripciones = Inscripcion::with(['alumno', 'grupo'])
                    ->where('grupo_id', $grupoId)
                    ->where(function ($q) use ($periodoId) {
                        $q->where('periodo_id', $periodoId)
                          ->orWhereNull('periodo_id');
                    })
                    ->where(function($q) {
                        $q->where('estado', 1)->orWhereNull('estado');
                    })
                    ->whereNull('deleted_at')
                    ->get();

                $inscripcionesIds = $inscripciones->pluck('id')->toArray();

                $evaluaciones = Evaluacion::whereIn('inscripcion_id', $inscripcionesIds)
                    ->where('unidad_id', $unidadId)
                    ->where(function ($q) use ($periodoId) {
                        $q->where('periodo_id', $periodoId)
                          ->orWhereNull('periodo_id');
                    })
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

                $alumnos = $inscripciones->sortBy(function($inscripcion) {
                    return $inscripcion->alumno->nombre . ' ' . $inscripcion->alumno->apellido_paterno;
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
