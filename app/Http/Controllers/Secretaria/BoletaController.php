<?php

namespace App\Http\Controllers\Secretaria;

use App\Enums\CatequesisCapability as C;
use App\Http\Controllers\Controller;
use App\Http\Support\CatequesisHttp;
use App\Models\Secretaria\Nivel;
use App\Models\Secretaria\Rubro;
use App\Models\Secretaria\Unidad;
use App\Queries\AccessibleAsignaciones;
use App\Queries\AccessibleCatequistas;
use App\Queries\AccessibleEvaluaciones;
use App\Queries\AccessibleInscripciones;
use App\Services\Authorization\CatequesisAccess;
use Illuminate\Http\Request;

class BoletaController extends Controller
{
    /**
     * Index — muestra los filtros y el listado de alumnos.
     */
    public function index(Request $request)
    {
        $request->validate([
            'catequista_id' => ['nullable', 'integer', 'min:1'],
            'nivel_id' => ['nullable', 'integer', 'min:1'],
            'grupo_id' => ['nullable', 'integer', 'min:1'],
        ]);
        $context = app(CatequesisHttp::class)->context($request, C::ViewBoletas);
        $periodoId = $context->activePeriodId;
        $catequistas = app(AccessibleCatequistas::class)->for($context)->orderBy('name')->get();
        $niveles = Nivel::whereIn('id', app(AccessibleAsignaciones::class)->for($context)->select('nivel_id'))->orderBy('nivel')->get();
        $gruposDisponibles = \App\Models\Secretaria\Grupo::whereIn('id', app(AccessibleAsignaciones::class)->for($context)->select('grupo_id'))->orderBy('nombre')->get();
        // Filtros seleccionados
        $filtros = [
            'catequista_id' => $request->input('catequista_id'),
            'nivel_id' => $request->input('nivel_id'),
            'grupo_id' => $request->input('grupo_id'),
        ];

        $alumnos = collect();

        // Solo consultar si se aplicó al menos un filtro
        if ($filtros['catequista_id'] || $filtros['nivel_id'] || $filtros['grupo_id']) {
            // Obtener asignaciones que coincidan con los filtros
            $asignaciones = app(AccessibleAsignaciones::class)->for($context)
                ->when($filtros['catequista_id'], fn ($q) => $q->where('catequista_id', $filtros['catequista_id']))
                ->when($filtros['nivel_id'], fn ($q) => $q->where('nivel_id', $filtros['nivel_id']))
                ->when($filtros['grupo_id'], fn ($q) => $q->where('grupo_id', $filtros['grupo_id']))
                ->with(['comunidad', 'grupo', 'nivel', 'catequista'])
                ->get();

            // Obtener los grupo_ids de esas asignaciones
            $grupoIds = $asignaciones->pluck('grupo_id')->unique()->toArray();

            if (! empty($grupoIds)) {
                $alumnos = app(AccessibleInscripciones::class)->for($context)
                    ->whereIn('grupo_id', $grupoIds)
                    ->whereNull('deleted_at')
                    ->with(['alumno.comunidad', 'alumno.tutores', 'grupo'])
                    ->get()
                    ->map(function ($inscripcion) use ($asignaciones) {
                        $asignacion = $asignaciones->where('grupo_id', $inscripcion->grupo_id)->sole();
                        $inscripcion->asignacion = $asignacion;

                        return $inscripcion;
                    });
            }
        }

        return view('secretaria.boletas.index', compact(
            'catequistas', 'niveles', 'gruposDisponibles', 'filtros', 'alumnos'
        ));
    }

    /**
     * Genera la vista de la boleta para impresión / PDF de un alumno.
     */
    public function generar(Request $request, $inscripcionId)
    {
        $request->validate(['asignacion_id' => ['nullable', 'integer', 'min:1']]);
        abort_unless(ctype_digit((string) $inscripcionId) && (int) $inscripcionId > 0, 404);
        $http = app(CatequesisHttp::class);
        $context = $http->context($request, C::ViewBoletas);
        $periodoId = $context->activePeriodId;
        $access = app(CatequesisAccess::class);
        $http->enforce($access->canViewBoleta($context, (int) $inscripcionId));
        $inscripcion = app(AccessibleInscripciones::class)->for($context)
            ->with(['alumno.comunidad', 'alumno.tutores', 'grupo', 'periodo'])->findOrFail($inscripcionId);
        $assignmentQuery = app(AccessibleAsignaciones::class)->for($context)
            ->where('grupo_id', $inscripcion->grupo_id)->where('periodo_id', $inscripcion->periodo_id);
        if ($request->filled('asignacion_id')) {
            $assignmentQuery->whereKey($request->integer('asignacion_id'));
        }
        $assignments = $assignmentQuery->with(['nivel', 'catequista', 'comunidad'])->get();
        abort_if($assignments->isEmpty(), 404);
        abort_unless($assignments->count() === 1, 409, 'Solicita revisión de la asignación a Secretaría.');
        $asignacion = $assignments->sole();
        $http->enforce($access->canUseInscripcionAsignacion($context, (int) $inscripcionId, $asignacion->id));
        $nivelId = $asignacion?->nivel_id;

        // Obtener las unidades de ese nivel ordenadas
        $unidades = Unidad::where('nivel_id', $nivelId)
            ->orderBy('numero')
            ->get();

        // Obtener los rubros disponibles ordenados por id
        $rubros = Rubro::orderBy('id')->get();

        // Obtener las evaluaciones del alumno (por inscripcion_id)
        $evaluaciones = app(AccessibleEvaluaciones::class)->for($context)->where('inscripcion_id', $inscripcionId)
            ->get();

        // Organizar las evaluaciones en una estructura: [unidad_id][rubro_id] => calificacion
        $calificacionesMap = [];
        foreach ($evaluaciones as $eval) {
            $calificacionesMap[$eval->unidad_id][$eval->rubro_id] = $eval->calificacion;
        }

        // Calcular el total de valor de los rubros (igual que la catequista)
        $totalRubros = (float) $rubros->sum('valor');

        // Calcular promedios por unidad usando la fórmula real de la catequista:
        // (suma_calificaciones / total_valor_rubros) * 10
        $promediosUnidad = [];
        foreach ($unidades as $unidad) {
            $califs = $calificacionesMap[$unidad->id] ?? [];
            if (count($califs) > 0 && $totalRubros > 0) {
                $sumaCalificaciones = array_sum($califs);
                $promedioUnidad = ($sumaCalificaciones / $totalRubros) * 10;
                $promediosUnidad[$unidad->id] = round($promedioUnidad, 1);
            } else {
                $promediosUnidad[$unidad->id] = null;
            }
        }

        // Promedio final general (promedio de todos los promedios de unidad)
        $promediosValidos = array_filter($promediosUnidad, fn ($v) => $v !== null);
        $promedioFinal = count($promediosValidos) > 0
            ? round(array_sum($promediosValidos) / count($promediosValidos), 1)
            : null;

        // Periodo texto
        $periodo = $inscripcion->periodo;
        $periodoTexto = $periodo
            ? ($periodo->fecha_inicio?->format('Y').' - '.$periodo->fecha_fin?->format('Y'))
            : (date('Y').' - '.(date('Y') + 1));

        return view('secretaria.boletas.boleta_pdf', compact(
            'inscripcion', 'asignacion', 'unidades', 'rubros',
            'calificacionesMap', 'promediosUnidad', 'promedioFinal',
            'periodoTexto'
        ));
    }
}
