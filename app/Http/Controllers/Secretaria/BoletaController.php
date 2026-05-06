<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use App\Models\Secretaria\AsignaGrupo;
use App\Models\Secretaria\Evaluacion;
use App\Models\Secretaria\Inscripcion;
use App\Models\Secretaria\Nivel;
use App\Models\Secretaria\Rubro;
use App\Models\Secretaria\Unidad;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BoletaController extends Controller
{
    /**
     * Index — muestra los filtros y el listado de alumnos.
     */
    public function index(Request $request)
    {
        $periodoId = session('periodo_activo_id');

        // Catálogos para los filtros
        $catequistas = User::where('role', 'catequista')
            ->where('status', 'aprobado')
            ->orderBy('name')
            ->get();

        $niveles = Nivel::orderBy('nivel')->get();

        // Grupos disponibles según asignaciones del periodo activo
        $gruposDisponibles = DB::table('asigna_grupo')
            ->join('grupos', 'asigna_grupo.grupo_id', '=', 'grupos.id')
            ->where(function ($q) use ($periodoId) {
                $q->where('asigna_grupo.periodo_id', $periodoId)
                  ->orWhereNull('asigna_grupo.periodo_id');
            })
            ->whereNull('asigna_grupo.deleted_at')
            ->select('grupos.id', 'grupos.nombre')
            ->distinct()
            ->orderBy('grupos.nombre')
            ->get();

        // Filtros seleccionados
        $filtros = [
            'catequista_id' => $request->input('catequista_id'),
            'nivel_id'      => $request->input('nivel_id'),
            'grupo_id'      => $request->input('grupo_id'),
        ];

        $alumnos = collect();

        // Solo consultar si se aplicó al menos un filtro
        if ($filtros['catequista_id'] || $filtros['nivel_id'] || $filtros['grupo_id']) {
            // Obtener asignaciones que coincidan con los filtros
            $asignaciones = AsignaGrupo::query()
                ->where(function ($q) use ($periodoId) {
                    $q->where('periodo_id', $periodoId)
                      ->orWhereNull('periodo_id');
                })
                ->when($filtros['catequista_id'], fn($q) => $q->where('catequista_id', $filtros['catequista_id']))
                ->when($filtros['nivel_id'], fn($q) => $q->where('nivel_id', $filtros['nivel_id']))
                ->when($filtros['grupo_id'], fn($q) => $q->where('grupo_id', $filtros['grupo_id']))
                ->with(['comunidad', 'grupo', 'nivel', 'catequista'])
                ->get();

            // Obtener los grupo_ids de esas asignaciones
            $grupoIds = $asignaciones->pluck('grupo_id')->unique()->toArray();

            if (!empty($grupoIds)) {
                $alumnos = Inscripcion::query()
                    ->whereIn('grupo_id', $grupoIds)
                    ->where(function ($q) use ($periodoId) {
                        $q->where('periodo_id', $periodoId)
                          ->orWhereNull('periodo_id');
                    })
                    ->whereNull('deleted_at')
                    ->with(['alumno.comunidad', 'alumno.tutores', 'grupo'])
                    ->get()
                    ->map(function ($inscripcion) use ($asignaciones) {
                        $asignacion = $asignaciones->firstWhere('grupo_id', $inscripcion->grupo_id);
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
        $periodoId = session('periodo_activo_id');

        $inscripcion = Inscripcion::with(['alumno.comunidad', 'alumno.tutores', 'grupo', 'periodo'])
            ->findOrFail($inscripcionId);

        // Encontrar la asignación correcta para obtener el nivel y la catequista
        $asignacionId = $request->input('asignacion_id');

        if ($asignacionId) {
            $asignacion = AsignaGrupo::where('id', $asignacionId)
                ->with(['nivel', 'catequista', 'comunidad'])
                ->first();
        } else {
            $asignacion = AsignaGrupo::where('grupo_id', $inscripcion->grupo_id)
                ->where(function ($q) use ($periodoId) {
                    $q->where('periodo_id', $periodoId)
                      ->orWhereNull('periodo_id');
                })
                ->with(['nivel', 'catequista', 'comunidad'])
                ->first();
        }

        $nivelId = $asignacion?->nivel_id;

        // Obtener las unidades de ese nivel ordenadas
        $unidades = Unidad::where('nivel_id', $nivelId)
            ->orderBy('numero')
            ->get();

        // Obtener los rubros disponibles ordenados por id
        $rubros = Rubro::orderBy('id')->get();

        // Obtener las evaluaciones del alumno (por inscripcion_id)
        $evaluaciones = Evaluacion::where('inscripcion_id', $inscripcionId)
            ->where(function ($q) use ($periodoId) {
                $q->where('periodo_id', $periodoId)
                  ->orWhereNull('periodo_id');
            })
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
        $promediosValidos = array_filter($promediosUnidad, fn($v) => $v !== null);
        $promedioFinal = count($promediosValidos) > 0
            ? round(array_sum($promediosValidos) / count($promediosValidos), 1)
            : null;

        // Periodo texto
        $periodo = $inscripcion->periodo;
        $periodoTexto = $periodo
            ? ($periodo->fecha_inicio?->format('Y') . ' - ' . $periodo->fecha_fin?->format('Y'))
            : (date('Y') . ' - ' . (date('Y') + 1));

        return view('secretaria.boletas.boleta_pdf', compact(
            'inscripcion', 'asignacion', 'unidades', 'rubros',
            'calificacionesMap', 'promediosUnidad', 'promedioFinal',
            'periodoTexto'
        ));
    }
}
