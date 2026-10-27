<?php

namespace App\Http\Controllers\Secretaria;

use App\Enums\CatequesisCapability as C;
use App\Http\Controllers\Controller;
use App\Http\Support\CatequesisHttp;
use App\Models\Secretaria\Grupo;
use App\Models\Secretaria\Nivel;
use App\Models\Secretaria\Rubro;
use App\Models\Secretaria\Unidad;
use App\Queries\AccessibleAsignaciones;
use App\Queries\AccessibleCatequistas;
use App\Queries\AccessibleEvaluaciones;
use App\Queries\AccessibleInscripciones;
use App\Services\Academics\GradeSummary;
use App\Services\Authorization\CatequesisAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BoletaController extends Controller
{
    public function index(Request $request)
    {
        $filtros = array_merge(['catequista_id' => null, 'nivel_id' => null, 'grupo_id' => null], $request->validate([
            'catequista_id' => ['nullable', 'integer', 'min:1'],
            'nivel_id' => ['nullable', 'integer', 'min:1'],
            'grupo_id' => ['nullable', 'integer', 'min:1'],
        ]));
        $context = app(CatequesisHttp::class)->context($request, C::ViewBoletas);
        $periodoId = $context->activePeriodId;
        $catequistas = app(AccessibleCatequistas::class)->for($context)->orderBy('name')->get();
        $niveles = Nivel::whereIn('id', app(AccessibleAsignaciones::class)->for($context)->where('periodo_id', $periodoId)->select('nivel_id'))->orderBy('nivel')->get();
        $gruposDisponibles = Grupo::whereIn('id', app(AccessibleAsignaciones::class)->for($context)->where('periodo_id', $periodoId)->select('grupo_id'))->orderBy('nombre')->get();
        $alumnos = collect();

        if (filled($filtros['catequista_id']) || filled($filtros['nivel_id']) || filled($filtros['grupo_id'])) {
            $asignaciones = app(AccessibleAsignaciones::class)->for($context)
                ->where('periodo_id', $periodoId)
                ->when(filled($filtros['catequista_id']), fn ($q) => $q->where('catequista_id', $filtros['catequista_id']))
                ->when(filled($filtros['nivel_id']), fn ($q) => $q->where('nivel_id', $filtros['nivel_id']))
                ->when(filled($filtros['grupo_id']), fn ($q) => $q->where('grupo_id', $filtros['grupo_id']))
                ->with(['comunidad', 'grupo', 'nivel', 'catequista'])->get();

            if ($asignaciones->isNotEmpty()) {
                $grupoIds = $asignaciones->pluck('grupo_id')->unique()->all();
                $alumnos = app(AccessibleInscripciones::class)->forBoletas($context)
                    ->whereIn('inscripciones.grupo_id', $grupoIds)
                    ->with(['alumno.comunidad', 'alumno.tutores', 'grupo'])->get()
                    ->map(function ($inscripcion) use ($asignaciones) {
                        $opciones = $asignaciones->filter(fn ($a) =>
                            (int) $a->grupo_id === (int) $inscripcion->grupo_id &&
                            (int) $a->periodo_id === (int) $inscripcion->periodo_id &&
                            ($inscripcion->asigna_grupo_id === null || (int) $inscripcion->asigna_grupo_id === (int) $a->id)
                        )->values();
                        $inscripcion->setRelation('opcionesBoleta', $opciones);
                        $inscripcion->setRelation('asignacionesBoleta', $opciones);
                        $inscripcion->setRelation('asignacion', $opciones->count() === 1 ? $opciones->first() : null);
                        return $inscripcion;
                    })->filter(fn ($i) => $i->opcionesBoleta->isNotEmpty())->values();
            }
        }

        return view('secretaria.boletas.index', compact('catequistas', 'niveles', 'gruposDisponibles', 'filtros', 'alumnos'));
    }

    public function generar(Request $request, $inscripcionId)
    {
        $request->validate(['asignacion_id' => ['nullable', 'integer', 'min:1']]);
        abort_unless(ctype_digit((string) $inscripcionId) && (int) $inscripcionId > 0, 404);
        $http = app(CatequesisHttp::class);
        $context = $http->context($request, C::ViewBoletas);
        $access = app(CatequesisAccess::class);
        $inscripcion = app(AccessibleInscripciones::class)->forBoletas($context)
            ->with(['alumno.comunidad', 'alumno.tutores', 'grupo', 'periodo'])->findOrFail($inscripcionId);

        $query = app(AccessibleAsignaciones::class)->for($context)
            ->where('grupo_id', $inscripcion->grupo_id)
            ->where('periodo_id', $inscripcion->periodo_id)
            ->when($inscripcion->asigna_grupo_id !== null, fn ($q) => $q->whereKey($inscripcion->asigna_grupo_id))
            ->when($request->filled('asignacion_id'), fn ($q) => $q->whereKey($request->integer('asignacion_id')));
        $assignments = $query->with(['nivel', 'catequista', 'comunidad'])->get();
        abort_if($assignments->isEmpty(), 404);
        if ($assignments->count() > 1) return view('secretaria.boletas.seleccionar', compact('inscripcion', 'assignments'));

        $asignacion = $assignments->sole();
        $http->enforce($access->canViewBoleta($context, (int) $inscripcionId, (int) $asignacion->id));
        $unidades = Unidad::where('nivel_id', $asignacion->nivel_id)->orderBy('numero')->get();
        $rubros = Rubro::orderBy('id')->get();
        $evaluaciones = app(AccessibleEvaluaciones::class)->forBoleta($context, (int) $inscripcionId, (int) $asignacion->id)->get();
        abort_if($evaluaciones->groupBy(fn ($e) => $e->unidad_id.':'.$e->rubro_id)->contains(fn ($items) => $items->count() > 1),
            409, 'Existen evaluaciones duplicadas. Solicita revisión a Secretaría.');

        $calificacionesMap = [];
        foreach ($evaluaciones as $eval) $calificacionesMap[$eval->unidad_id][$eval->rubro_id] = $eval->calificacion;
        $resumen = app(GradeSummary::class)->calculate($unidades, $evaluaciones, (float) $rubros->sum('valor'));
        $promediosUnidad = $resumen['unidades'];
        $promedioFinal = $resumen['final'];
        $periodo = $inscripcion->periodo;
        $periodoTexto = $periodo?->fecha_inicio && $periodo?->fecha_fin
            ? Carbon::parse($periodo->fecha_inicio)->format('Y').' - '.Carbon::parse($periodo->fecha_fin)->format('Y')
            : 'No disponible';

        return view('secretaria.boletas.boleta_pdf', compact(
            'inscripcion', 'asignacion', 'unidades', 'rubros', 'calificacionesMap', 'promediosUnidad', 'promedioFinal', 'periodoTexto'
        ));
    }
}
