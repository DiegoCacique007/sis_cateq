<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Secretaria\Alumno;
use App\Models\Secretaria\Comunidad;
use App\Models\Secretaria\Nivel;

class AlumnoComunidadController extends Controller
{
    public function index(Request $request)
    {
        $periodoActivoId = session('periodo_activo_id');

        $query = Alumno::with([
            'comunidad',
            'inscripciones' => function ($q) use ($periodoActivoId) {
                $q->where('periodo_id', $periodoActivoId)
                    ->addSelect('inscripciones.*')
                    ->addSelect([
                        'nivel_nombre' => DB::table('asigna_grupo')
                            ->join('niveles', 'asigna_grupo.nivel_id', '=', 'niveles.id')
                            ->select('niveles.nivel')
                            ->whereColumn('asigna_grupo.grupo_id', 'inscripciones.grupo_id')
                            ->whereColumn('asigna_grupo.periodo_id', 'inscripciones.periodo_id')
                            ->whereNull('asigna_grupo.deleted_at')
                            ->whereNull('niveles.deleted_at')
                            ->limit(1),

                        'nivel_numero' => DB::table('asigna_grupo')
                            ->join('niveles', 'asigna_grupo.nivel_id', '=', 'niveles.id')
                            ->select('niveles.numero')
                            ->whereColumn('asigna_grupo.grupo_id', 'inscripciones.grupo_id')
                            ->whereColumn('asigna_grupo.periodo_id', 'inscripciones.periodo_id')
                            ->whereNull('asigna_grupo.deleted_at')
                            ->whereNull('niveles.deleted_at')
                            ->limit(1),

                        'sacramento_nombre' => DB::table('asigna_grupo')
                            ->join('niveles', 'asigna_grupo.nivel_id', '=', 'niveles.id')
                            ->select('niveles.sacramento')
                            ->whereColumn('asigna_grupo.grupo_id', 'inscripciones.grupo_id')
                            ->whereColumn('asigna_grupo.periodo_id', 'inscripciones.periodo_id')
                            ->whereNull('asigna_grupo.deleted_at')
                            ->whereNull('niveles.deleted_at')
                            ->limit(1),
                    ]);
            }
        ]);

        if ($request->filled('comunidad_id')) {
            $query->where('comunidad_id', $request->comunidad_id);
        }

        $query->whereHas('inscripciones', function ($q) use ($request, $periodoActivoId) {
            $q->where('periodo_id', $periodoActivoId);

            if ($request->filled('sacramento') || $request->filled('numero_nivel')) {
                $q->whereExists(function ($subquery) use ($request) {
                    $subquery
                        ->select(DB::raw(1))
                        ->from('asigna_grupo')
                        ->join('niveles', 'asigna_grupo.nivel_id', '=', 'niveles.id')
                        ->whereColumn('asigna_grupo.grupo_id', 'inscripciones.grupo_id')
                        ->whereColumn('asigna_grupo.periodo_id', 'inscripciones.periodo_id')
                        ->whereNull('asigna_grupo.deleted_at')
                        ->whereNull('niveles.deleted_at');

                    if ($request->filled('sacramento')) {
                        $subquery->where('niveles.sacramento', $request->sacramento);
                    }

                    if ($request->filled('numero_nivel')) {
                        $subquery->where('niveles.numero', $request->numero_nivel);
                    }
                });
            }
        });

        $registros = $query
            ->paginate(15)
            ->withQueryString();

        $comunidades = Comunidad::orderBy('comunidad')->get();

        $nivelesDisponibles = Nivel::select('numero')
            ->distinct()
            ->orderBy('numero')
            ->get();

        return view('secretaria.alumnos_comunidades.index', compact(
            'registros',
            'comunidades',
            'nivelesDisponibles'
        ));
    }
}
